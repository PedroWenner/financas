<?php
/**
 * DespesaFixaController — CRUD de despesas fixas recorrentes.
 */

declare(strict_types=1);

class DespesaFixaController
{
    private DespesaFixaModel $model;
    private CategoriaModel   $categorias;

    public function __construct()
    {
        Auth::requireLogin();
        $this->model      = new DespesaFixaModel();
        $this->categorias = new CategoriaModel();
    }

    /** GET /despesas-fixas */
    public function index(): void
    {
        $uid    = Auth::id();
        $fixas  = $this->model->listar($uid, false); // inclui inativas para mostrar histórico
        $total  = $this->model->totalMensalEstimado($uid);

        Response::view('despesas/fixas_index', [
            'titulo' => 'Despesas Fixas',
            'fixas'  => $fixas,
            'total'  => $total,
        ]);
    }

    /** GET /despesas-fixas/criar */
    public function criar(): void
    {
        $uid = Auth::id();
        Response::view('despesas/fixas_criar', [
            'titulo'     => 'Nova Despesa Fixa',
            'categorias' => $this->categorias->listar($uid, 'despesa'),
            'old'        => ['data_inicio' => date('Y-m-01'), 'dia_vencimento' => 10],
        ]);
    }

    /** POST /despesas-fixas/criar */
    public function store(): void
    {
        CsrfHelper::check();
        $uid   = Auth::id();
        $dados = $this->extrairDados();
        $erros = $this->validar($dados);

        if (!empty($erros)) {
            Response::view('despesas/fixas_criar', [
                'titulo'     => 'Nova Despesa Fixa',
                'categorias' => $this->categorias->listar($uid, 'despesa'),
                'errors'     => $erros,
                'old'        => $dados,
            ]);
            return;
        }

        $this->model->criar($uid, $dados);
        Response::redirectWith('/despesas-fixas', 'success', 'Despesa fixa cadastrada!');
    }

    /** GET /despesas-fixas/editar?id=X */
    public function editar(): void
    {
        $uid  = Auth::id();
        $id   = Request::getInt('id');
        $fixa = $this->model->findById($id, $uid);

        if (!$fixa) {
            Response::redirectWith('/despesas-fixas', 'error', 'Despesa fixa não encontrada.');
        }

        Response::view('despesas/fixas_editar', [
            'titulo'     => 'Editar Despesa Fixa',
            'fixa'       => $fixa,
            'categorias' => $this->categorias->listar($uid, 'despesa'),
        ]);
    }

    /** POST /despesas-fixas/editar */
    public function update(): void
    {
        CsrfHelper::check();
        $uid   = Auth::id();
        $id    = (int) Request::post('id', 0);
        $dados = $this->extrairDados();
        $erros = $this->validar($dados);

        if (!empty($erros)) {
            Response::view('despesas/fixas_editar', [
                'titulo'     => 'Editar Despesa Fixa',
                'fixa'       => array_merge(['id' => $id], $dados),
                'categorias' => $this->categorias->listar($uid, 'despesa'),
                'errors'     => $erros,
                'old'        => $dados,
            ]);
            return;
        }

        $ok = $this->model->atualizar($id, $uid, $dados);
        Response::redirectWith('/despesas-fixas', $ok ? 'success' : 'info', $ok ? 'Atualizada com sucesso!' : 'Nenhuma alteração.');
    }

    /** POST /despesas-fixas/finalizar */
    public function finalizar(): void
    {
        CsrfHelper::check();
        $uid     = Auth::id();
        $id      = (int) Request::post('id', 0);
        $dataFim = Request::post('data_fim', '');

        $v = new ValidationHelper(['data_fim' => $dataFim]);
        $v->required('data_fim', 'Data de finalização')->date('data_fim', 'Data de finalização');

        if (!$v->passes()) {
            Response::redirectWith('/despesas-fixas', 'error', $v->error('data_fim'));
            return;
        }

        $ok = $this->model->finalizar($id, $uid, $dataFim);
        Response::redirectWith(
            '/despesas-fixas',
            $ok ? 'success' : 'error',
            $ok ? 'Despesa fixa finalizada. Lançamentos fora do período foram removidos.' : 'Despesa fixa não encontrada.'
        );
    }

    /** POST /despesas-fixas/deletar — exclusão permanente com todos os lançamentos */
    public function deletar(): void
    {
        CsrfHelper::check();
        $uid = Auth::id();
        $id  = (int) Request::post('id', 0);

        $ok = $this->model->excluirComLancamentos($id, $uid);
        Response::redirectWith('/despesas-fixas', $ok ? 'success' : 'error', $ok ? 'Despesa fixa e todos os lançamentos excluídos.' : 'Registro não encontrado.');
    }

    /** POST /despesas-fixas/ativar */
    public function ativar(): void
    {
        CsrfHelper::check();
        $uid = Auth::id();
        $id  = (int) Request::post('id', 0);

        $this->model->ativar($id, $uid);
        Response::redirectWith('/despesas-fixas', 'success', 'Despesa fixa reativada.');
    }

    // ──────────────────────────────────────────────
    // Privados
    // ──────────────────────────────────────────────

    private function extrairDados(): array
    {
        return [
            'descricao'      => Request::post('descricao', ''),
            'valor'          => ValidationHelper::parseMoeda(Request::post('valor', '0')),
            'dia_vencimento' => (int) Request::post('dia_vencimento', 1),
            'data_inicio'    => Request::post('data_inicio', ''),
            'data_fim'       => Request::post('data_fim', ''),
            'categoria_id'   => (int) Request::post('categoria_id', 0),
            'observacoes'    => Request::post('observacoes', ''),
        ];
    }

    private function validar(array $dados): array
    {
        $v = new ValidationHelper($dados);
        $v->required('descricao', 'Descrição')
          ->max('descricao', 'Descrição', 200)
          ->required('data_inicio', 'Data de início')
          ->date('data_inicio', 'Data de início');

        $erros = $v->errors();

        if ($dados['valor'] <= 0) {
            $erros['valor'] = 'O valor deve ser maior que zero.';
        }

        $dia = $dados['dia_vencimento'];
        if ($dia < 1 || $dia > 28) {
            $erros['dia_vencimento'] = 'O dia deve ser entre 1 e 28.';
        }

        if (!empty($dados['data_fim']) && $dados['data_fim'] < $dados['data_inicio']) {
            $erros['data_fim'] = 'A data de fim não pode ser anterior à data de início.';
        }

        return $erros;
    }
}
