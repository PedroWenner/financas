<?php
/**
 * ParcelamentoController — CRUD de compras parceladas.
 */

declare(strict_types=1);

class ParcelamentoController
{
    private ParcelamentoModel $model;
    private CartaoModel       $cartoes;
    private CategoriaModel    $categorias;

    public function __construct()
    {
        Auth::requireLogin();
        $this->model      = new ParcelamentoModel();
        $this->cartoes    = new CartaoModel();
        $this->categorias = new CategoriaModel();
    }

    /** GET /parcelamentos */
    public function index(): void
    {
        $uid    = Auth::id();
        $filtro = Request::getString('filtro', 'ativos');

        $parcelamentos = $this->model->listar($uid, $filtro);
        $totalPendente = $this->model->totalPendente($uid);

        Response::view('parcelamentos/index', [
            'titulo'        => 'Parcelamentos',
            'parcelamentos' => $parcelamentos,
            'totalPendente' => $totalPendente,
            'filtro'        => $filtro,
        ]);
    }

    /** GET /parcelamentos/criar */
    public function criar(): void
    {
        $uid = Auth::id();
        Response::view('parcelamentos/criar', [
            'titulo'     => 'Nova Compra Parcelada',
            'cartoes'    => $this->cartoes->listar($uid),
            'categorias' => $this->categorias->listar($uid, 'despesa'),
            'old'        => ['data_compra' => date('Y-m-d'), 'numero_parcelas' => 2],
        ]);
    }

    /** POST /parcelamentos/criar */
    public function store(): void
    {
        CsrfHelper::check();
        $uid   = Auth::id();
        $dados = $this->extrairDados();
        $erros = $this->validar($dados);

        if (!empty($erros)) {
            Response::view('parcelamentos/criar', [
                'titulo'     => 'Nova Compra Parcelada',
                'cartoes'    => $this->cartoes->listar($uid),
                'categorias' => $this->categorias->listar($uid, 'despesa'),
                'errors'     => $erros,
                'old'        => $dados,
            ]);
            return;
        }

        // Calcula data da primeira parcela automaticamente se não foi informada
        if (empty($dados['data_primeira_parcela'])) {
            $dados['data_primeira_parcela'] = $this->model->calcularDataPrimeiraParcela(
                $dados['data_compra'],
                $dados['cartao_id'] ?: null
            );
        }

        try {
            $id = $this->model->criarComParcelas($uid, $dados);
            Response::redirectWith(
                '/parcelamentos/detalhe?id=' . $id,
                'success',
                'Parcelamento criado! ' . $dados['numero_parcelas'] . ' parcelas geradas automaticamente.'
            );
        } catch (\Exception $e) {
            Response::view('parcelamentos/criar', [
                'titulo'     => 'Nova Compra Parcelada',
                'cartoes'    => $this->cartoes->listar($uid),
                'categorias' => $this->categorias->listar($uid, 'despesa'),
                'errors'     => ['geral' => 'Erro ao criar parcelamento. Tente novamente.'],
                'old'        => $dados,
            ]);
        }
    }

    /** GET /parcelamentos/detalhe?id=X */
    public function detalhe(): void
    {
        $uid           = Auth::id();
        $id            = Request::getInt('id');
        $parcelamento  = $this->model->buscarComProgresso($id, $uid);

        if (!$parcelamento) {
            Response::redirectWith('/parcelamentos', 'error', 'Parcelamento não encontrado.');
        }

        $parcelas = $this->model->listarParcelas($id);

        // Separa pagas e pendentes para o gráfico de progresso
        $pagas    = array_filter($parcelas, fn($p) => $p['pago']);
        $pendentes = array_filter($parcelas, fn($p) => !$p['pago']);

        Response::view('parcelamentos/detalhe', [
            'titulo'       => 'Detalhe do Parcelamento',
            'parcelamento' => $parcelamento,
            'parcelas'     => $parcelas,
            'pagas'        => count($pagas),
            'pendentes'    => count($pendentes),
        ]);
    }

    /** POST /parcelamentos/pagar */
    public function pagarParcela(): void
    {
        CsrfHelper::check();
        $uid        = Auth::id();
        $parcelaId  = (int) Request::post('parcela_id', 0);
        $parc_id    = (int) Request::post('parcelamento_id', 0);
        $pago       = (bool) Request::post('pago', 0);

        $this->model->marcarParcelaPaga($parcelaId, $uid, $pago);

        $msg = $pago ? 'Parcela marcada como paga!' : 'Parcela marcada como pendente.';
        Response::redirectWith(
            '/parcelamentos/detalhe?id=' . $parc_id,
            'success',
            $msg
        );
    }

    /** POST /parcelamentos/bloquear */
    public function bloquear(): void
    {
        CsrfHelper::check();
        $uid         = Auth::id();
        $id          = (int) Request::post('id', 0);
        $mes         = Request::post('bloqueado_a_partir', '');
        $fromDetalhe = (bool) Request::post('from_detalhe', 0);
        $redirect    = $fromDetalhe ? '/parcelamentos/detalhe?id=' . $id : '/parcelamentos';

        if (empty($mes) || !preg_match('/^\d{4}-\d{2}$/', $mes)) {
            Response::redirectWith($redirect, 'error', 'Informe um mês/ano válido.');
            return;
        }

        $data    = $mes . '-01';
        $dataFmt = date('m/Y', strtotime($data));
        $ok      = $this->model->bloquear($id, $uid, $data);
        Response::redirectWith($redirect, $ok ? 'success' : 'error',
            $ok ? "Parcelamento bloqueado a partir de {$dataFmt}." : 'Registro não encontrado.'
        );
    }

    /** POST /parcelamentos/desbloquear */
    public function desbloquear(): void
    {
        CsrfHelper::check();
        $uid         = Auth::id();
        $id          = (int) Request::post('id', 0);
        $fromDetalhe = (bool) Request::post('from_detalhe', 0);
        $redirect    = $fromDetalhe ? '/parcelamentos/detalhe?id=' . $id : '/parcelamentos';

        $ok = $this->model->desbloquear($id, $uid);
        Response::redirectWith($redirect, $ok ? 'success' : 'error',
            $ok ? 'Bloqueio removido. O parcelamento voltará a aparecer nas despesas.' : 'Registro não encontrado.'
        );
    }

    /** POST /parcelamentos/excluir */
    public function destroy(): void
    {
        CsrfHelper::check();
        $uid = Auth::id();
        $id  = (int) Request::post('id', 0);

        $this->model->excluir($id, $uid);
        Response::redirectWith('/parcelamentos', 'success', 'Parcelamento e todas as parcelas foram removidos.');
    }

    // ──────────────────────────────────────────────
    // Privados
    // ──────────────────────────────────────────────

    private function extrairDados(): array
    {
        return [
            'descricao'             => Request::post('descricao', ''),
            'valor_total'           => ValidationHelper::parseMoeda(Request::post('valor_total', '0')),
            'numero_parcelas'       => (int) Request::post('numero_parcelas', 1),
            'data_compra'           => Request::post('data_compra', date('Y-m-d')),
            'data_primeira_parcela' => Request::post('data_primeira_parcela', ''),
            'cartao_id'             => (int) Request::post('cartao_id', 0),
            'categoria_id'          => (int) Request::post('categoria_id', 0),
            'observacoes'           => Request::post('observacoes', ''),
        ];
    }

    private function validar(array $dados): array
    {
        $v = new ValidationHelper($dados);
        $v->required('descricao',    'Descrição')
          ->max('descricao',         'Descrição', 200)
          ->required('data_compra',  'Data da compra')
          ->date('data_compra',      'Data da compra');

        $erros = $v->errors();

        if ($dados['valor_total'] <= 0) {
            $erros['valor_total'] = 'O valor total deve ser maior que zero.';
        }

        if ($dados['numero_parcelas'] < 2) {
            $erros['numero_parcelas'] = 'O número de parcelas deve ser pelo menos 2.';
        }

        if ($dados['numero_parcelas'] > 96) {
            $erros['numero_parcelas'] = 'O número máximo de parcelas é 96.';
        }

        if (!empty($dados['data_primeira_parcela'])) {
            $v2 = new ValidationHelper(['data_primeira_parcela' => $dados['data_primeira_parcela']]);
            $v2->date('data_primeira_parcela', 'Data da primeira parcela');
            $erros = array_merge($erros, $v2->errors());
        }

        return $erros;
    }
}
