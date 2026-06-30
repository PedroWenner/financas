<?php
/**
 * Auth — Gerenciamento de autenticação e sessão.
 */

declare(strict_types=1);

class Auth
{
    private const REMEMBER_COOKIE  = 'rememberme';
    private const REMEMBER_DAYS    = 30;

    // ── Sessão ───────────────────────────────────────────────────────

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
        return !empty($_SESSION['logged_in']);
    }

    /** Redireciona para /login se não autenticado (tenta o cookie antes). */
    public static function requireLogin(): void
    {
        if (!self::check() && !self::checkRememberToken()) {
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

    /** Encerra a sessão e limpa o cookie de remember-me. */
    public static function logout(): void
    {
        self::clearRememberToken();

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

    // ── Remember-me ──────────────────────────────────────────────────

    /**
     * Gera um token persistente e define o cookie "Lembrar-me".
     * Deve ser chamado após Auth::login() quando o usuário marcou o checkbox.
     */
    public static function setRememberToken(int $uid): void
    {
        try {
            $raw       = base64_encode(random_bytes(32));   // 44 chars, URL-safe enough
            $hash      = hash('sha256', $raw);
            $expiresTs = time() + (self::REMEMBER_DAYS * 86400);
            $expiresAt = date('Y-m-d H:i:s', $expiresTs);

            Database::getInstance()
                ->prepare("INSERT INTO remember_tokens (usuario_id, token_hash, expires_at) VALUES (:uid, :hash, :exp)")
                ->execute([':uid' => $uid, ':hash' => $hash, ':exp' => $expiresAt]);

            // Limpa tokens expirados deste usuário (housekeeping)
            Database::getInstance()
                ->prepare("DELETE FROM remember_tokens WHERE usuario_id = :uid AND expires_at < NOW()")
                ->execute([':uid' => $uid]);

            setcookie(self::REMEMBER_COOKIE, $uid . ':' . $raw, [
                'expires'  => $expiresTs,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Strict',
                'secure'   => self::isHttps(),
            ]);
        } catch (\Exception) {
            // Falha silenciosa: o login normal já funcionou, só o remember-me falhou
        }
    }

    /**
     * Verifica o cookie de remember-me e, se válido, faz login automático.
     * Retorna true se o login automático foi bem-sucedido.
     */
    public static function checkRememberToken(): bool
    {
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? '';
        if ($cookie === '') return false;

        $parts = explode(':', $cookie, 2);
        if (count($parts) !== 2) {
            self::clearRememberCookie();
            return false;
        }

        [$uid, $raw] = $parts;
        $uid = (int) $uid;
        if ($uid <= 0 || $raw === '') {
            self::clearRememberCookie();
            return false;
        }

        try {
            $hash = hash('sha256', $raw);
            $stmt = Database::getInstance()->prepare(
                "SELECT rt.id, u.id AS uid, u.nome, u.email
                 FROM remember_tokens rt
                 JOIN usuarios u ON u.id = rt.usuario_id
                 WHERE rt.usuario_id = :uid
                   AND rt.token_hash = :hash
                   AND rt.expires_at > NOW()
                   AND u.ativo = 1
                 LIMIT 1"
            );
            $stmt->execute([':uid' => $uid, ':hash' => $hash]);
            $row = $stmt->fetch();

            if (!$row) {
                self::clearRememberCookie();
                return false;
            }

            // Rotaciona o token a cada uso para invalidar cópias roubadas
            $newRaw       = base64_encode(random_bytes(32));
            $newHash      = hash('sha256', $newRaw);
            $newExpiresTs = time() + (self::REMEMBER_DAYS * 86400);
            $newExpiresAt = date('Y-m-d H:i:s', $newExpiresTs);

            Database::getInstance()
                ->prepare("UPDATE remember_tokens SET token_hash = :hash, expires_at = :exp WHERE id = :id")
                ->execute([':hash' => $newHash, ':exp' => $newExpiresAt, ':id' => $row['id']]);

            setcookie(self::REMEMBER_COOKIE, $row['uid'] . ':' . $newRaw, [
                'expires'  => $newExpiresTs,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Strict',
                'secure'   => self::isHttps(),
            ]);

            self::login(['id' => $row['uid'], 'nome' => $row['nome'], 'email' => $row['email']]);
            return true;

        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Remove o token do banco e apaga o cookie (chamado no logout).
     */
    public static function clearRememberToken(): void
    {
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? '';
        if ($cookie === '') return;

        $parts = explode(':', $cookie, 2);
        if (count($parts) === 2) {
            [$uid, $raw] = $parts;
            $uid = (int) $uid;
            if ($uid > 0 && $raw !== '') {
                try {
                    $hash = hash('sha256', $raw);
                    Database::getInstance()
                        ->prepare("DELETE FROM remember_tokens WHERE usuario_id = :uid AND token_hash = :hash")
                        ->execute([':uid' => $uid, ':hash' => $hash]);
                } catch (\Exception) {
                    // Ignora erros de DB durante logout
                }
            }
        }

        self::clearRememberCookie();
    }

    private static function clearRememberCookie(): void
    {
        setcookie(self::REMEMBER_COOKIE, '', [
            'expires'  => time() - 86400,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure'   => self::isHttps(),
        ]);
    }

    private static function isHttps(): bool
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }
}
