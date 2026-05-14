<?php
/**
 * CsrfHelper — Proteção contra Cross-Site Request Forgery.
 */

declare(strict_types=1);

class CsrfHelper
{
    private const TOKEN_KEY = '_csrf_token';

    /** Gera ou retorna o token CSRF da sessão. */
    public static function token(): string
    {
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    /** Valida o token enviado via POST. */
    public static function validate(string $token): bool
    {
        return !empty($_SESSION[self::TOKEN_KEY])
            && hash_equals($_SESSION[self::TOKEN_KEY], $token);
    }

    /** Valida e encerra se inválido. */
    public static function check(): void
    {
        $token = Request::post('_csrf_token', '');
        if (!self::validate($token)) {
            http_response_code(403);
            die('Requisição inválida. Por favor, recarregue a página e tente novamente.');
        }
    }

    /** Retorna o campo hidden HTML com o token. */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . self::token() . '">';
    }
}
