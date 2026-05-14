<?php
/**
 * AlcatechFinanças — Front Controller
 * Ponto de entrada único de todas as requisições HTTP.
 */

declare(strict_types=1);

// Define a raiz do projeto (um nível acima de /public)
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH',  BASE_PATH . '/app');
define('CORE_PATH', BASE_PATH . '/core');

// Carrega configurações e autoloader
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once CORE_PATH  . '/autoload.php';
require_once CORE_PATH  . '/Database.php';
require_once CORE_PATH  . '/Auth.php';
require_once CORE_PATH  . '/Request.php';
require_once CORE_PATH  . '/Response.php';
require_once CORE_PATH  . '/Router.php';
require_once APP_PATH   . '/Helpers/FormatHelper.php';
require_once APP_PATH   . '/Helpers/CsrfHelper.php';
require_once APP_PATH   . '/Helpers/ValidationHelper.php';

// Inicia sessão segura
Auth::startSession();

// Carrega o mapa de rotas e despacha a requisição
$router = new Router();
require_once BASE_PATH . '/config/routes.php';
$router->dispatch();
