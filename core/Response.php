<?php
/**
 * Response — Helpers para respostas HTTP.
 */

declare(strict_types=1);

class Response
{
    /** Redireciona para uma URL. */
    public static function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    /** Redireciona com mensagem flash. */
    public static function redirectWith(string $url, string $tipo, string $mensagem): never
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
        self::redirect($url);
    }

    /** Retorna resposta JSON. */
    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Define e exibe a mensagem flash na view. */
    public static function getFlash(): ?array
    {
        if (!empty($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    /** Renderiza uma view com layout. */
    public static function view(
        string $view,
        array $data = [],
        string $layout = 'main'
    ): void {
        // Extrai variáveis para a view
        extract($data, EXTR_SKIP);

        $viewPath   = APP_PATH . '/Views/' . str_replace('.', '/', $view) . '.php';
        $layoutPath = APP_PATH . '/Views/layouts/' . $layout . '.php';

        if (!file_exists($viewPath)) {
            die("View não encontrada: {$viewPath}");
        }

        // Captura o conteúdo da view
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // Renderiza o layout com o conteúdo
        require $layoutPath;
    }
}
