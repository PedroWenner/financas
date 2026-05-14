<?php
/**
 * PerfilController — Dados do usuário e alteração de senha.
 */

declare(strict_types=1);

class PerfilController
{
    private UsuarioModel $model;

    public function __construct()
    {
        Auth::requireLogin();
        $this->model = new UsuarioModel();
    }

    /** GET /perfil */
    public function index(): void
    {
        $usuario = $this->model->buscarPorId(Auth::id());

        Response::view('perfil/index', [
            'titulo'  => 'Meu Perfil',
            'usuario' => $usuario,
        ]);
    }

    /** POST /perfil/atualizar — atualiza nome e e-mail */
    public function atualizar(): void
    {
        CsrfHelper::check();
        $uid   = Auth::id();
        $nome  = trim(Request::post('nome', ''));
        $email = trim(Request::post('email', ''));

        $erros = [];

        if (mb_strlen($nome) < 2) {
            $erros['nome'] = 'O nome deve ter pelo menos 2 caracteres.';
        }
        if (mb_strlen($nome) > 100) {
            $erros['nome'] = 'O nome não pode ultrapassar 100 caracteres.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros['email'] = 'Informe um e-mail válido.';
        }

        if (!empty($erros)) {
            $usuario = $this->model->buscarPorId($uid);
            Response::view('perfil/index', [
                'titulo'  => 'Meu Perfil',
                'usuario' => $usuario,
                'errors'  => $erros,
                'aba'     => 'dados',
            ]);
            return;
        }

        $ok = $this->model->atualizarPerfil($uid, $nome, $email);

        if (!$ok) {
            $usuario = $this->model->buscarPorId($uid);
            Response::view('perfil/index', [
                'titulo'  => 'Meu Perfil',
                'usuario' => $usuario,
                'errors'  => ['email' => 'Este e-mail já está em uso por outra conta.'],
                'aba'     => 'dados',
            ]);
            return;
        }

        Response::redirectWith('/perfil', 'success', 'Perfil atualizado com sucesso!');
    }

    /** POST /perfil/senha — altera a senha */
    public function atualizarSenha(): void
    {
        CsrfHelper::check();
        $uid         = Auth::id();
        $senhaAtual  = Request::post('senha_atual', '');
        $novaSenha   = Request::post('nova_senha', '');
        $confirmacao = Request::post('confirmacao_senha', '');

        $erros = [];

        if (empty($senhaAtual)) {
            $erros['senha_atual'] = 'Informe a senha atual.';
        }

        if (mb_strlen($novaSenha) < 6) {
            $erros['nova_senha'] = 'A nova senha deve ter pelo menos 6 caracteres.';
        }

        if ($novaSenha !== $confirmacao) {
            $erros['confirmacao_senha'] = 'As senhas não coincidem.';
        }

        // Verifica senha atual
        if (empty($erros)) {
            if (!$this->model->verificarSenha($uid, $senhaAtual)) {
                $erros['senha_atual'] = 'Senha atual incorreta.';
            }
        }

        if (!empty($erros)) {
            $usuario = $this->model->buscarPorId($uid);
            Response::view('perfil/index', [
                'titulo'  => 'Meu Perfil',
                'usuario' => $usuario,
                'errors'  => $erros,
                'aba'     => 'senha',
            ]);
            return;
        }

        $this->model->atualizarSenha($uid, $novaSenha);
        Response::redirectWith('/perfil', 'success', 'Senha alterada com sucesso!');
    }
}
