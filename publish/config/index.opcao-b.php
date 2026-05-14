<?php
/**
 * AlcatechFinancas — Front Controller (PRODUCAO — Opcao B)
 *
 * Opcao B: tudo dentro do public_html/
 *   /public_html/
 *     index.php    (este arquivo)
 *     .htaccess    (com regras de bloqueio das pastas)
 *     assets/
 *     app/         (protegido por .htaccess e regra RewriteRule)
 *     config/      (protegido)
 *     core/        (protegido)
 *     migrations/  (protegido)
 *
 * Use quando NAO tiver acesso SSH para criar pastas
 * fora do public_html (planos Basic/Premium da Hostinger).
 *
 * Risco: menor isolamento — pastas protegidas por .htaccess,
 * mas ainda no document root. Prefira Opcao A quando possivel.
 */

declare(strict_types=1);

// ── BASE_PATH e o proprio public_html/ ───────────────────────
// __DIR__ = /home/u123456789/public_html
define('BASE_PATH', __DIR__);
define('APP_PATH',  BASE_PATH . '/app');
define('CORE_PATH', BASE_PATH . '/core');

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

Auth::startSession();

$router = new Router();
require_once BASE_PATH . '/config/routes.php';
$router->dispatch();
