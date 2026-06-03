<?php
/**
 * ReceitaController — CRUD de receitas.
 */

declare(strict_types=1);

class ReceitaController
{
    private ReceitaModel   $model;
    private CategoriaModel $categorias;

    public function __construct()
    {
        Auth::requireLogin();
        $this->model      = new ReceitaModel();
        $this->categorias = new CategoriaModel();
    }

    /** GET /receitas */
    public function index(): void
    {
        $uid = Auth::id();
        $ano = Request::getInt('ano', (int) date('Y'));
        $mes = Request::getInt('mes', (int) date('n'));
        $pag = max(1, Request::getInt('pag', 1));
        $cat = Request::getInt('cat', 0);
        $tip = Request::getString('tipo');

        $mes = max(1, min(12, $mes));
        $ano = max(2000, min(2100, $ano));

        $receitas  = $this->model->listar($uid, $ano, $mes, $cat ?: null, $tip ?: null, $pag);
        $total     = $this->model->totalMes($uid, $ano, $mes);
        $count     = $this->model->contar($uid, $ano, $mes);
        $categos   = $this->categorias->listar($uid, 'receita');

        Response::view('receitas/index', [
            'titulo'     => 'Receitas',
            'receitas'   => $receitas,
            'total'      => $total,
            'count'      => $count,
            'categorias' => $categos,
            'ano'        => $ano,
            'mes'        => $mes,
            'pagina'     => $pag,
            'catFiltro'  => $cat,
            'tipoFiltro' => $tip,
        ]);
    }

    /** GET /receitas/criar */
    public function criar(): void
    {
        $uid = Auth::id();
        $ref = $this->refSeguro(Request::getString('ref'));
        Response::view('receitas/criar', [
            'titulo'     => 'Nova Receita',
            'categorias' => $this->categorias->listar($uid, 'receita'),
            'ref'        => $ref,
        ]);
    }

    /** POST /receitas/criar */
    public function store(): void
    {
        CsrfHelper::check();
        $uid = Auth::id();
        $ref = $this->refSeguro(Request::post('ref', ''));

        $dados = $this->extrairDados();
        $erros = $this->validar($dados);

        if (!empty($erros)) {
            Response::view('receitas/criar', [
                'titulo'     => 'Nova Receita',
                'categorias' => $this->categorias->listar($uid, 'receita'),
                'errors'     => $erros,
                'old'        => $dados,
                'ref'        => $ref,
            ]);
            return;
        }

        $this->model->criar($uid, $dados);
        $url = $ref ?: '/receitas?mes=' . date('n') . '&ano=' . date('Y');
        Response::redirectWith($url, 'success', 'Receita cadastrada com sucesso!');
    }

    /** GET /receitas/editar?id=X */
    public function editar(): void
    {
        $uid     = Auth::id();
        $id      = Request::getInt('id');
        $ref     = $this->refSeguro(Request::getString('ref'));
        $receita = $this->model->findById($id, $uid);

        if (!$receita) {
            Response::redirectWith($ref ?: '/receitas', 'error', 'Receita não encontrada.');
        }

        Response::view('receitas/editar', [
            'titulo'     => 'Editar Receita',
            'receita'    => $receita,
            'categorias' => $this->categorias->listar($uid, 'receita'),
            'ref'        => $ref,
        ]);
    }

    /** POST /receitas/editar */
    public function update(): void
    {
        CsrfHelper::check();
        $uid = Auth::id();
        $id  = (int) Request::post('id', 0);
        $ref = $this->refSeguro(Request::post('ref', ''));

        $dados = $this->extrairDados();
        $erros = $this->validar($dados);

        if (!empty($erros)) {
            Response::view('receitas/editar', [
                'titulo'     => 'Editar Receita',
                'receita'    => array_merge(['id' => $id], $dados),
                'categorias' => $this->categorias->listar($uid, 'receita'),
                'errors'     => $erros,
                'old'        => $dados,
                'ref'        => $ref,
            ]);
            return;
        }

        $ok  = $this->model->atualizar($id, $uid, $dados);
        $url = $ref ?: '/receitas?mes=' . date('n') . '&ano=' . date('Y');
        Response::redirectWith($url, $ok ? 'success' : 'info', $ok ? 'Receita atualizada!' : 'Nenhuma alteração.');
    }

    /** POST /receitas/excluir */
    public function destroy(): void
    {
        CsrfHelper::check();
        $uid = Auth::id();
        $id  = (int) Request::post('id', 0);
        $ref = $this->refSeguro(Request::post('ref', ''));

        $this->model->excluir($id, $uid);
        Response::redirectWith($ref ?: '/receitas', 'success', 'Receita removida.');
    }

    // ──────────────────────────────────────────────
    // Privados
    // ──────────────────────────────────────────────

    private function refSeguro(string $ref): string
    {
        $ref = trim($ref);
        return (str_starts_with($ref, '/') && !str_starts_with($ref, '//')) ? $ref : '';
    }

    private function extrairDados(): array
    {
        return [
            'descricao'    => Request::post('descricao', ''),
            'valor'        => ValidationHelper::parseMoeda(Request::post('valor', '0')),
            'data'         => Request::post('data', ''),
            'tipo_receita' => Request::post('tipo_receita', 'outro'),
            'categoria_id' => (int) Request::post('categoria_id', 0),
            'observacoes'  => Request::post('observacoes', ''),
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

        return $erros;
    }
}
