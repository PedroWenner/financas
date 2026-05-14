<?php
/**
 * AlcatechFinancas — Front Controller (PRODUCAO — Opcao A)
 *
 * Opcao A: pastas separadas na Hostinger
 *   /public_html/        <- este arquivo fica aqui
 *   /alcatech_app/       <- app/, config/, core/, migrations/
 *
 * Estrutura no servidor:
 *   /home/u123456789/public_html/index.php  (este arquivo)
 *   /home/u123456789/alcatech_app/app/
 *   /home/u123456789/alcatech_app/config/
 *   /home/u123456789/alcatech_app/core/
 *
 * Por que Opcao A e mais segura:
 *   Os arquivos PHP do app ficam FORA do document root,
 *   impossibilitando acesso direto via navegador.
 */

declare(strict_types=1);

// ── BASE_PATH aponta para alcatech_app/ (fora do public_html) ──
// __DIR__ = /home/u123456789/public_html
// /../alcatech_app = /home/u123456789/alcatech_app
define('BASE_PATH', realpath(__DIR__ . '/../alcatech_app'));

if (!BASE_PATH || !is_dir(BASE_PATH)) {
    http_response_code(500);
    die('Erro de configuracao: diretorio da aplicacao nao encontrado.');
}

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
