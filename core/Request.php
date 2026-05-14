<?php
/**
 * Request — Encapsula os dados da requisição HTTP.
 */

declare(strict_types=1);

class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function uri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return '/' . trim($uri ?: '/', '/') ?: '/';
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function isGet(): bool
    {
        return self::method() === 'GET';
    }

    /** Obtém valor de POST, sanitizado. */
    public static function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /** Obtém valor de GET, sanitizado. */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /** Obtém todos os dados de POST. */
    public static function all(): array
    {
        return $_POST;
    }

    /** Obtém valor inteiro de GET. */
    public static function getInt(string $key, int $default = 0): int
    {
        return (int) ($_GET[$key] ?? $default);
    }

    /** Obtém valor de GET como string segura. */
    public static function getString(string $key, string $default = ''): string
    {
        $val = $_GET[$key] ?? $default;
        return htmlspecialchars(strip_tags((string) $val), ENT_QUOTES, 'UTF-8');
    }
}
