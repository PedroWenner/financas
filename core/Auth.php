<?php
/**
 * Auth — Gerenciamento de autenticação e sessão.
 */

declare(strict_types=1);

class Auth
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /** Autentica o usuário e inicializa a sessão. */
    public static function login(array $usuario): void
    {
        session_regenerate_id(true);

        $_SESSION['user_id']    = $usuario['id'];
        $_SESSION['user_nome']  = $usuario['nome'];
        $_SESSION['user_email'] = $usuario['email'];
        $_SESSION['logged_in']  = true;
        $_SESSION['login_ip']   = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['login_ua']   = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /** Verifica se há sessão válida. */
    public static function check(): bool
    {
        if (empty($_SESSION['logged_in'])) {
            return false;
        }

        // Valida consistência mínima (previne hijacking básico)
        $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!empty($_SESSION['login_ip']) && $_SESSION['login_ip'] !== $currentIp) {
            self::logout();
            return false;
        }

        return true;
    }

    /** Redireciona para /login se não autenticado. */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
    }

    /** Retorna o ID do usuário logado (0 se não autenticado). */
    public static function id(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    /** Retorna o nome do usuário logado. */
    public static function nome(): string
    {
        return $_SESSION['user_nome'] ?? '';
    }

    /** Retorna o e-mail do usuário logado. */
    public static function email(): string
    {
        return $_SESSION['user_email'] ?? '';
    }

    /** Encerra a sessão. */
    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();
    }
}
