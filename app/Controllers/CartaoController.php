<?php
/**
 * CartaoController — CRUD de cartões de crédito.
 */

declare(strict_types=1);

class CartaoController
{
    private CartaoModel $model;

    public function __construct()
    {
        Auth::requireLogin();
        $this->model = new CartaoModel();
    }

    /** GET /cartoes */
    public function index(): void
    {
        $uid = Auth::id();
        $ano = Request::getInt('ano', (int) date('Y'));
        $mes = Request::getInt('mes', (int) date('n'));

        $cartoes = $this->model->listarComGastos($uid, $ano, $mes);

        Response::view('cartoes/index', [
            'titulo'  => 'Cartões de Crédito',
            'cartoes' => $cartoes,
            'ano'     => $ano,
            'mes'     => $mes,
        ]);
    }

    /** GET /cartoes/criar */
    public function criar(): void
    {
        Response::view('cartoes/criar', [
            'titulo' => 'Novo Cartão',
        ]);
    }

    /** POST /cartoes/criar */
    public function store(): void
    {
        CsrfHelper::check();
        $uid   = Auth::id();
        $dados = $this->extrairDados();
        $erros = $this->validar($dados);

        if (!empty($erros)) {
            Response::view('cartoes/criar', [
                'titulo' => 'Novo Cartão',
                'errors' => $erros,
                'old'    => $dados,
            ]);
            return;
        }

        $this->model->criar($uid, $dados);
        Response::redirectWith('/cartoes', 'success', 'Cartão cadastrado com sucesso!');
    }

    /** GET /cartoes/editar?id=X */
    public function editar(): void
    {
        $uid    = Auth::id();
        $id     = Request::getInt('id');
        $cartao = $this->model->buscarPorId($id, $uid);

        if (!$cartao) {
            Response::redirectWith('/cartoes', 'error', 'Cartão não encontrado.');
        }

        Response::view('cartoes/editar', [
            'titulo' => 'Editar Cartão',
            'cartao' => $cartao,
        ]);
    }

    /** POST /cartoes/editar */
    public function update(): void
    {
        CsrfHelper::check();
        $uid   = Auth::id();
        $id    = (int) Request::post('id', 0);
        $dados = $this->extrairDados();
        $erros = $this->validar($dados);

        if (!empty($erros)) {
            Response::view('cartoes/editar', [
                'titulo' => 'Editar Cartão',
                'cartao' => array_merge(['id' => $id], $dados),
                'errors' => $erros,
                'old'    => $dados,
            ]);
            return;
        }

        $ok = $this->model->atualizar($id, $uid, $dados);
        Response::redirectWith('/cartoes', $ok ? 'success' : 'info', $ok ? 'Cartão atualizado!' : 'Nenhuma alteração.');
    }

    /** POST /cartoes/excluir */
    public function destroy(): void
    {
        CsrfHelper::check();
        $uid = Auth::id();
        $id  = (int) Request::post('id', 0);

        $this->model->desativar($id, $uid);
        Response::redirectWith('/cartoes', 'success', 'Cartão removido.');
    }

    /** GET /cartoes/fatura?id=X&mes=Y&ano=Z */
    public function fatura(): void
    {
        $uid    = Auth::id();
        $id     = Request::getInt('id');
        $ano    = Request::getInt('ano', (int) date('Y'));
        $mes    = Request::getInt('mes', (int) date('n'));
        $cartao = $this->model->buscarPorId($id, $uid);

        if (!$cartao) {
            Response::redirectWith('/cartoes', 'error', 'Cartão não encontrado.');
        }

        $despesas = $this->model->getFatura($id, $uid, $ano, $mes);
        $total    = array_sum(array_column($despesas, 'valor'));

        Response::view('cartoes/fatura', [
            'titulo'   => 'Fatura — ' . $cartao['nome'],
            'cartao'   => $cartao,
            'despesas' => $despesas,
            'total'    => $total,
            'ano'      => $ano,
            'mes'      => $mes,
        ]);
    }

    // ──────────────────────────────────────────────
    // Privados
    // ──────────────────────────────────────────────

    private function extrairDados(): array
    {
        return [
            'nome'           => trim(Request::post('nome', '')),
            'bandeira'       => Request::post('bandeira', 'outro'),
            'limite'         => ValidationHelper::parseMoeda(Request::post('limite', '0')),
            'dia_fechamento' => (int) Request::post('dia_fechamento', 1),
            'dia_vencimento' => (int) Request::post('dia_vencimento', 10),
            'cor'            => Request::post('cor', '#2563eb'),
        ];
    }

    private function validar(array $dados): array
    {
        $v = new ValidationHelper($dados);
        $v->required('nome', 'Nome')->max('nome', 'Nome', 80);

        $erros = $v->errors();

        if ($dados['limite'] < 0) {
            $erros['limite'] = 'O limite não pode ser negativo.';
        }

        if ($dados['dia_fechamento'] < 1 || $dados['dia_fechamento'] > 28) {
            $erros['dia_fechamento'] = 'O dia de fechamento deve ser entre 1 e 28.';
        }

        if ($dados['dia_vencimento'] < 1 || $dados['dia_vencimento'] > 28) {
            $erros['dia_vencimento'] = 'O dia de vencimento deve ser entre 1 e 28.';
        }

        return $erros;
    }
}
