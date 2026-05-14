<?php
/**
 * CategoriaController — CRUD de categorias.
 */

declare(strict_types=1);

class CategoriaController
{
    private CategoriaModel $model;

    public function __construct()
    {
        Auth::requireLogin();
        $this->model = new CategoriaModel();
    }

    /** GET /categorias */
    public function index(): void
    {
        $uid = Auth::id();
        $categorias = $this->model->listar($uid);

        Response::view('categorias/index', [
            'titulo'     => 'Categorias',
            'categorias' => $categorias,
        ]);
    }

    /** POST /categorias/criar */
    public function store(): void
    {
        CsrfHelper::check();
        $uid = Auth::id();

        $dados = [
            'nome'  => trim(Request::post('nome', '')),
            'tipo'  => Request::post('tipo', 'ambos'),
            'icone' => Request::post('icone', ''),
            'cor'   => Request::post('cor', '#6c757d'),
        ];

        $v = new ValidationHelper($dados);
        $v->required('nome', 'Nome')->max('nome', 'Nome', 80);

        if (!$v->passes()) {
            Response::redirectWith('/categorias', 'error', $v->errors()['nome']);
        }

        $this->model->criar($uid, $dados);
        Response::redirectWith('/categorias', 'success', 'Categoria criada com sucesso!');
    }

    /** POST /categorias/editar */
    public function update(): void
    {
        CsrfHelper::check();
        $uid = Auth::id();
        $id  = (int) Request::post('id', 0);

        $dados = [
            'nome'  => trim(Request::post('nome', '')),
            'tipo'  => Request::post('tipo', 'ambos'),
            'icone' => Request::post('icone', ''),
            'cor'   => Request::post('cor', '#6c757d'),
        ];

        $v = new ValidationHelper($dados);
        $v->required('nome', 'Nome')->max('nome', 'Nome', 80);

        if (!$v->passes()) {
            Response::redirectWith('/categorias', 'error', $v->errors()['nome']);
        }

        $ok = $this->model->atualizar($id, $uid, $dados);
        $msg = $ok ? 'Categoria atualizada!' : 'Nenhuma alteração realizada.';
        Response::redirectWith('/categorias', $ok ? 'success' : 'info', $msg);
    }

    /** POST /categorias/excluir */
    public function destroy(): void
    {
        CsrfHelper::check();
        $uid = Auth::id();
        $id  = (int) Request::post('id', 0);

        if ($this->model->emUso($id)) {
            Response::redirectWith(
                '/categorias', 'error',
                'Esta categoria está em uso e não pode ser excluída.'
            );
        }

        $this->model->desativar($id, $uid);
        Response::redirectWith('/categorias', 'success', 'Categoria removida.');
    }
}
