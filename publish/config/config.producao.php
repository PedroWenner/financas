<?php
/**
 * AlcatechFinancas — Configuracoes de PRODUCAO
 * Substitui config/config.php no ambiente da Hostinger.
 *
 * IMPORTANTE: Edite APP_URL com seu dominio real.
 */

declare(strict_types=1);

// ──────────────────────────────────────────────
// Ambiente
// ──────────────────────────────────────────────
define('APP_NAME',    'AlcatechFinanças');
define('APP_VERSION', '1.0.0');
define('APP_ENV',     'production');
define('APP_URL',     'https://financa.alcati.com.br');

// ──────────────────────────────────────────────
// Erros — NUNCA exibir em producao
// ──────────────────────────────────────────────
ini_set('display_errors',         '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

// Log de erros para arquivo (opcional — configure o caminho)
// ini_set('log_errors', '1');
// ini_set('error_log', dirname(__DIR__) . '/logs/php_errors.log');

// ──────────────────────────────────────────────
// Timezone e locale
// ──────────────────────────────────────────────
date_default_timezone_set('America/Sao_Paulo');
setlocale(LC_MONETARY, 'pt_BR.UTF-8', 'pt_BR', 'portuguese');

// ──────────────────────────────────────────────
// Seguranca de sessao (HTTPS obrigatorio em prod)
// ──────────────────────────────────────────────
ini_set('session.cookie_httponly',  '1');
ini_set('session.use_strict_mode',  '1');
ini_set('session.gc_maxlifetime',   '7200');
ini_set('session.cookie_samesite',  'Strict');
ini_set('session.cookie_secure',    '1');   // Requer HTTPS ativo
