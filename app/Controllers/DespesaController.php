<?php
/**
 * DespesaController — CRUD de despesas variáveis e parceladas.
 */

declare(strict_types=1);

class DespesaController
{
    private DespesaModel   $model;
    private CategoriaModel $categorias;
    private CartaoModel    $cartoes;

    public function __construct()
    {
        Auth::requireLogin();
        $this->model      = new DespesaModel();
        $this->categorias = new CategoriaModel();
        $this->cartoes    = new CartaoModel();
    }

    /** GET /despesas */
    public function index(): void
    {
        $uid = Auth::id();
        $ano = Request::getInt('ano', (int) date('Y'));
        $mes = Request::getInt('mes', (int) date('n'));
        $pag = max(1, Request::getInt('pag', 1));

        $mes = max(1, min(12, $mes));
        $ano = max(2000, min(2100, $ano));

        (new DespesaFixaModel())->gerarLancamentos($uid, $ano, $mes);

        $catFiltro    = Request::getInt('cat', 0);
        $tipoFiltro   = Request::getString('tipo');
        $cartaoFiltro = Request::getInt('cartao', 0);
        $situacao     = Request::getString('situacao');
        $pagoFiltro   = $situacao !== '' ? (int) $situacao : null;

        $despesas      = $this->model->listar(
            $uid, $ano, $mes,
            $catFiltro    ?: null,
            $tipoFiltro   ?: null,
            $cartaoFiltro ?: null,
            null, $pagoFiltro, $pag
        );
        // Cards de resumo: sempre mês completo (sem filtro de tipo/situação)
        $total         = $this->model->totalMes($uid, $ano, $mes, $catFiltro ?: null, null, $cartaoFiltro ?: null);
        $porTipo       = $this->model->totalPorTipo($uid, $ano, $mes, $catFiltro ?: null, $cartaoFiltro ?: null);
        // Rodapé da tabela: respeita todos os filtros ativos
        $totalFiltrado = $this->model->totalMes($uid, $ano, $mes, $catFiltro ?: null, $tipoFiltro ?: null, $cartaoFiltro ?: null, $pagoFiltro);
        $count         = $this->model->contar($uid, $ano, $mes, $tipoFiltro ?: null, $catFiltro ?: null, $cartaoFiltro ?: null, $pagoFiltro);

        Response::view('despesas/index', [
            'titulo'         => 'Despesas',
            'despesas'       => $despesas,
            'total'          => $total,
            'totalFiltrado'  => $totalFiltrado,
            'porTipo'        => $porTipo,
            'count'          => $count,
            'categorias'     => $this->categorias->listar($uid, 'despesa'),
            'cartoes'        => $this->cartoes->listar($uid),
            'ano'            => $ano,
            'mes'            => $mes,
            'pagina'         => $pag,
            'catFiltro'      => $catFiltro,
            'tipoFiltro'     => $tipoFiltro,
            'cartaoFiltro'   => $cartaoFiltro,
            'situacao'       => $situacao,
        ]);
    }

    /** GET /despesas/criar */
    public function criar(): void
    {
        $uid = Auth::id();
        $ref = $this->refSeguro(Request::getString('ref'));
        Response::view('despesas/criar', [
            'titulo'     => 'Nova Despesa',
            'categorias' => $this->categorias->listar($uid, 'despesa'),
            'cartoes'    => $this->cartoes->listar($uid),
            'old'        => ['data' => date('Y-m-d'), 'tipo' => 'variavel'],
            'ref'        => $ref,
        ]);
    }

    /** POST /despesas/criar */
    public function store(): void
    {
        CsrfHelper::check();
        $uid   = Auth::id();
        $ref   = $this->refSeguro(Request::post('ref', ''));
        $dados = $this->extrairDados();
        $erros = $this->validar($dados);

        if (!empty($erros)) {
            Response::view('despesas/criar', [
                'titulo'     => 'Nova Despesa',
                'categorias' => $this->categorias->listar($uid, 'despesa'),
                'cartoes'    => $this->cartoes->listar($uid),
                'errors'     => $erros,
                'old'        => $dados,
                'ref'        => $ref,
            ]);
            return;
        }

        $this->model->criar($uid, $dados);
        if ($ref) {
            $url = $ref;
        } else {
            ['mes' => $mes, 'ano' => $ano] = $this->mesFatura($dados, $uid);
            $url = '/despesas?mes=' . $mes . '&ano=' . $ano;
        }
        Response::redirectWith($url, 'success', 'Despesa cadastrada com sucesso!');
    }

    /** GET /despesas/editar?id=X */
    public function editar(): void
    {
        $uid     = Auth::id();
        $id      = Request::getInt('id');
        $ref     = $this->refSeguro(Request::getString('ref'));
        $despesa = $this->model->buscarComJoins($id, $uid);

        if (!$despesa) {
            Response::redirectWith($ref ?: '/despesas', 'error', 'Despesa não encontrada.');
        }

        Response::view('despesas/editar', [
            'titulo'     => 'Editar Despesa',
            'despesa'    => $despesa,
            'categorias' => $this->categorias->listar($uid, 'despesa'),
            'cartoes'    => $this->cartoes->listar($uid),
            'ref'        => $ref,
        ]);
    }

    /** POST /despesas/editar */
    public function update(): void
    {
        CsrfHelper::check();
        $uid   = Auth::id();
        $id    = (int) Request::post('id', 0);
        $ref   = $this->refSeguro(Request::post('ref', ''));
        $dados = $this->extrairDados();
        $erros = $this->validar($dados);

        if (!empty($erros)) {
            Response::view('despesas/editar', [
                'titulo'     => 'Editar Despesa',
                'despesa'    => array_merge(['id' => $id], $dados),
                'categorias' => $this->categorias->listar($uid, 'despesa'),
                'cartoes'    => $this->cartoes->listar($uid),
                'errors'     => $erros,
                'old'        => $dados,
                'ref'        => $ref,
            ]);
            return;
        }

        $ok = $this->model->atualizar($id, $uid, $dados);
        if ($ref) {
            $url = $ref;
        } else {
            ['mes' => $mes, 'ano' => $ano] = $this->mesFatura($dados, $uid);
            $url = '/despesas?mes=' . $mes . '&ano=' . $ano;
        }
        Response::redirectWith($url, $ok ? 'success' : 'info', $ok ? 'Despesa atualizada!' : 'Nenhuma alteração.');
    }

    /** POST /despesas/pagar */
    public function pagar(): void
    {
        CsrfHelper::check();
        $uid  = Auth::id();
        $id   = (int) Request::post('id', 0);
        $pago = (bool) Request::post('pago', 0);

        $this->model->marcarPago($id, $uid, $pago);
        $msg = $pago ? 'Despesa marcada como paga!' : 'Despesa marcada como pendente.';
        Response::redirectWith($this->urlRetorno(), 'success', $msg);
    }

    /** POST /despesas/excluir */
    public function destroy(): void
    {
        CsrfHelper::check();
        $uid = Auth::id();
        $id  = (int) Request::post('id', 0);

        $this->model->excluir($id, $uid);
        Response::redirectWith($this->urlRetorno(), 'success', 'Despesa removida.');
    }

    // ──────────────────────────────────────────────
    // Privados
    // ──────────────────────────────────────────────

    private function refSeguro(string $ref): string
    {
        $ref = trim($ref);
        return (str_starts_with($ref, '/') && !str_starts_with($ref, '//')) ? $ref : '';
    }

    /** Monta URL de retorno preservando mês/ano e filtros ativos do POST. */
    private function urlRetorno(): string
    {
        $mes    = max(1, min(12, (int) Request::post('mes', (int) date('n'))));
        $ano    = max(2000, min(2100, (int) Request::post('ano', (int) date('Y'))));
        $url    = "/despesas?mes={$mes}&ano={$ano}";

        $cat    = (int) Request::post('cat', 0);
        $tipo   = Request::post('tipo', '');
        $cartao = (int) Request::post('cartao', 0);

        if ($cat)      $url .= "&cat={$cat}";
        if ($tipo)     $url .= "&tipo=" . urlencode($tipo);
        if ($cartao)   $url .= "&cartao={$cartao}";
        $situacao = Request::post('situacao', '');
        if ($situacao !== '') $url .= "&situacao=" . urlencode($situacao);

        return $url;
    }

    /** Retorna o mês/ano da fatura do cartão para a data da despesa. */
    private function mesFatura(array $dados, int $uid): array
    {
        $data = $dados['data'] ?: date('Y-m-d');
        $mes  = (int) date('n', strtotime($data));
        $ano  = (int) date('Y', strtotime($data));

        if (!empty($dados['cartao_id'])) {
            $cartao = $this->cartoes->buscarPorId((int) $dados['cartao_id'], $uid);
            if ($cartao) {
                $diaFechamento = (int) $cartao['dia_fechamento'];
                $diaCompra     = (int) date('j', strtotime($data));
                if ($diaCompra >= $diaFechamento) {
                    $faturaDate = (new DateTimeImmutable($data))->modify('first day of next month');
                    $mes = (int) $faturaDate->format('n');
                    $ano = (int) $faturaDate->format('Y');
                }
            }
        }

        return ['mes' => $mes, 'ano' => $ano];
    }

    private function extrairDados(): array
    {
        $pago = (bool) Request::post('pago', 0);
        return [
            'descricao'       => Request::post('descricao', ''),
            'valor'           => ValidationHelper::parseMoeda(Request::post('valor', '0')),
            'data'            => Request::post('data', ''),
            'tipo'            => Request::post('tipo', 'variavel'),
            'forma_pagamento' => Request::post('forma_pagamento', 'outro'),
            'categoria_id'    => (int) Request::post('categoria_id', 0),
            'cartao_id'       => (int) Request::post('cartao_id', 0),
            'pago'            => $pago,
            'data_pagamento'  => $pago ? date('Y-m-d') : Request::post('data_pagamento', ''),
            'observacoes'     => Request::post('observacoes', ''),
        ];
    }

    private function validar(array $dados): array
    {
        $v = new ValidationHelper($dados);
        $v->required('descricao', 'Descrição')
          ->max('descricao', 'Descrição', 200)
          ->required('data', 'Data')
          ->date('data', 'Data');

        $erros = $v->errors();

        if ($dados['valor'] <= 0) {
            $erros['valor'] = 'O valor deve ser maior que zero.';
        }

        if ($dados['forma_pagamento'] === 'credito' && empty($dados['cartao_id'])) {
            $erros['cartao_id'] = 'Selecione o cartão para pagamento no crédito.';
        }

        return $erros;
    }
}
