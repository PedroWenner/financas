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

        $catFiltro    = Request::getInt('cat', 0);
        $tipoFiltro   = Request::getString('tipo');
        $cartaoFiltro = Request::getInt('cartao', 0);

        $despesas  = $this->model->listar(
            $uid, $ano, $mes,
            $catFiltro    ?: null,
            $tipoFiltro   ?: null,
            $cartaoFiltro ?: null,
            null, $pag
        );
        $total     = $this->model->totalMes($uid, $ano, $mes);
        $porTipo   = $this->model->totalPorTipo($uid, $ano, $mes);
        $count     = $this->model->contar($uid, $ano, $mes, $tipoFiltro ?: null);

        Response::view('despesas/index', [
            'titulo'       => 'Despesas',
            'despesas'     => $despesas,
            'total'        => $total,
            'porTipo'      => $porTipo,
            'count'        => $count,
            'categorias'   => $this->categorias->listar($uid, 'despesa'),
            'cartoes'      => $this->cartoes->listar($uid),
            'ano'          => $ano,
            'mes'          => $mes,
            'pagina'       => $pag,
            'catFiltro'    => $catFiltro,
            'tipoFiltro'   => $tipoFiltro,
            'cartaoFiltro' => $cartaoFiltro,
        ]);
    }

    /** GET /despesas/criar */
    public function criar(): void
    {
        $uid = Auth::id();
        Response::view('despesas/criar', [
            'titulo'     => 'Nova Despesa',
            'categorias' => $this->categorias->listar($uid, 'despesa'),
            'cartoes'    => $this->cartoes->listar($uid),
            'old'        => ['data' => date('Y-m-d'), 'tipo' => 'variavel'],
        ]);
    }

    /** POST /despesas/criar */
    public function store(): void
    {
        CsrfHelper::check();
        $uid   = Auth::id();
        $dados = $this->extrairDados();
        $erros = $this->validar($dados);

        if (!empty($erros)) {
            Response::view('despesas/criar', [
                'titulo'     => 'Nova Despesa',
                'categorias' => $this->categorias->listar($uid, 'despesa'),
                'cartoes'    => $this->cartoes->listar($uid),
                'errors'     => $erros,
                'old'        => $dados,
            ]);
            return;
        }

        $this->model->criar($uid, $dados);
        $data = $dados['data'] ?: date('Y-m-d');
        $url  = '/despesas?mes=' . (int)date('n', strtotime($data)) . '&ano=' . (int)date('Y', strtotime($data));
        Response::redirectWith($url, 'success', 'Despesa cadastrada com sucesso!');
    }

    /** GET /despesas/editar?id=X */
    public function editar(): void
    {
        $uid     = Auth::id();
        $id      = Request::getInt('id');
        $despesa = $this->model->buscarComJoins($id, $uid);

        if (!$despesa) {
            Response::redirectWith('/despesas', 'error', 'Despesa não encontrada.');
        }

        Response::view('despesas/editar', [
            'titulo'     => 'Editar Despesa',
            'despesa'    => $despesa,
            'categorias' => $this->categorias->listar($uid, 'despesa'),
            'cartoes'    => $this->cartoes->listar($uid),
        ]);
    }

    /** POST /despesas/editar */
    public function update(): void
    {
        CsrfHelper::check();
        $uid   = Auth::id();
        $id    = (int) Request::post('id', 0);
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
            ]);
            return;
        }

        $ok  = $this->model->atualizar($id, $uid, $dados);
        $data = $dados['data'] ?: date('Y-m-d');
        $url  = '/despesas?mes=' . (int)date('n', strtotime($data)) . '&ano=' . (int)date('Y', strtotime($data));
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
        Response::redirectWith('/despesas', 'success', $msg);
    }

    /** POST /despesas/excluir */
    public function destroy(): void
    {
        CsrfHelper::check();
        $uid = Auth::id();
        $id  = (int) Request::post('id', 0);

        $this->model->excluir($id, $uid);
        Response::redirectWith('/despesas', 'success', 'Despesa removida.');
    }

    // ──────────────────────────────────────────────
    // Privados
    // ──────────────────────────────────────────────

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
