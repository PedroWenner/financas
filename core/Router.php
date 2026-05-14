<?php
/**
 * Router — Roteador simples de URLs.
 */

declare(strict_types=1);

class Router
{
    private array $routes = [];

    /** Registra uma rota. */
    public function add(string $method, string $uri, string $action): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'uri'    => $uri,
            'action' => $action,
        ];
    }

    /** Despacha a requisição para o controller correto. */
    public function dispatch(): void
    {
        $method = Request::method();
        $uri    = Request::uri();

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['uri'] === $uri) {
                $this->callAction($route['action']);
                return;
            }
        }

        // Nenhuma rota encontrada
        $this->notFound();
    }

    /** Instancia o controller e chama o método (action). */
    private function callAction(string $action): void
    {
        [$controllerName, $method] = explode('@', $action, 2);

        $controllerFile = APP_PATH . '/Controllers/' . $controllerName . '.php';

        if (!file_exists($controllerFile)) {
            $this->notFound();
            return;
        }

        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            $this->notFound();
            return;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $method)) {
            $this->notFound();
            return;
        }

        $controller->$method();
    }

    private function notFound(): never
    {
        http_response_code(404);
        // Usa layout adequado conforme autenticação do visitante
        $layout = Auth::check() ? 'main' : 'auth';
        Response::view('errors/404', ['titulo' => 'Página não encontrada'], $layout);
        exit;
    }
}
