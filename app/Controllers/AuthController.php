<?php
/**
 * AuthController — Login, cadastro e logout.
 */

declare(strict_types=1);

class AuthController
{
    private UsuarioModel $model;

    public function __construct()
    {
        $this->model = new UsuarioModel();
    }

    /** GET /login */
    public function loginForm(): void
    {
        if (Auth::check() || Auth::checkRememberToken()) {
            Response::redirect('/dashboard');
        }
        Response::view('auth/login', ['titulo' => 'Entrar'], 'auth');
    }

    /** POST /login */
    public function login(): void
    {
        CsrfHelper::check();

        $email = trim(Request::post('email', ''));
        $senha = Request::post('senha', '');

        $lembrar = Request::post('lembrar') === '1';

        $v = new ValidationHelper(['email' => $email, 'senha' => $senha]);
        $v->required('email', 'E-mail')
          ->email('email', 'E-mail')
          ->required('senha', 'Senha');

        if (!$v->passes()) {
            Response::view('auth/login', [
                'titulo' => 'Entrar',
                'errors' => $v->errors(),
                'old'    => ['email' => $email, 'lembrar' => $lembrar],
            ], 'auth');
            return;
        }

        $usuario = $this->model->autenticar($email, $senha);

        if (!$usuario) {
            Response::view('auth/login', [
                'titulo' => 'Entrar',
                'errors' => ['geral' => 'E-mail ou senha incorretos.'],
                'old'    => ['email' => $email, 'lembrar' => $lembrar],
            ], 'auth');
            return;
        }

        Auth::login($usuario);

        if ($lembrar) {
            Auth::setRememberToken((int) $usuario['id']);
        }

        Response::redirect('/dashboard');
    }

    /** GET /cadastro */
    public function cadastroForm(): void
    {
        if (Auth::check()) {
            Response::redirect('/dashboard');
        }
        Response::view('auth/cadastro', ['titulo' => 'Criar conta'], 'auth');
    }

    /** POST /cadastro */
    public function cadastro(): void
    {
        CsrfHelper::check();

        $nome  = trim(Request::post('nome', ''));
        $email = trim(Request::post('email', ''));
        $senha = Request::post('senha', '');
        $conf  = Request::post('senha_confirmacao', '');

        $v = new ValidationHelper([
            'nome'  => $nome,
            'email' => $email,
            'senha' => $senha,
        ]);
        $v->required('nome',  'Nome')
          ->max('nome', 'Nome', 100)
          ->required('email', 'E-mail')
          ->email('email', 'E-mail')
          ->required('senha', 'Senha')
          ->min('senha', 'Senha', 6);

        $errors = $v->errors();

        if ($senha !== $conf) {
            $errors['senha_confirmacao'] = 'As senhas não coincidem.';
        }

        if (!empty($errors)) {
            Response::view('auth/cadastro', [
                'titulo' => 'Criar conta',
                'errors' => $errors,
                'old'    => ['nome' => $nome, 'email' => $email],
            ], 'auth');
            return;
        }

        $id = $this->model->criar($nome, $email, $senha);

        if ($id === 0) {
            Response::view('auth/cadastro', [
                'titulo' => 'Criar conta',
                'errors' => ['email' => 'Este e-mail já está cadastrado.'],
                'old'    => ['nome' => $nome, 'email' => $email],
            ], 'auth');
            return;
        }

        // Insere categorias padrão para o novo usuário
        $this->model->inserirCategoriasPadrao($id);

        // Faz login automático após cadastro
        $usuario = $this->model->findByEmail($email);
        Auth::login($usuario);

        Response::redirectWith('/dashboard', 'success', 'Bem-vindo ao AlcatechFinanças, ' . $nome . '!');
    }

    /** GET /sair */
    public function logout(): void
    {
        Auth::logout();
        Response::redirect('/login');
    }
}
