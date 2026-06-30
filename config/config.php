<?php
/**
 * Configurações globais da aplicação.
 */

declare(strict_types=1);

// ──────────────────────────────────────────────
// Ambiente
// ──────────────────────────────────────────────
define('APP_NAME',    'AlcatechFinanças');
define('APP_VERSION', '1.0.0');
define('APP_ENV',     getenv('APP_ENV') ?: 'development'); // 'development' | 'production'
define('APP_URL',     getenv('APP_URL') ?: 'http://localhost');

// ──────────────────────────────────────────────
// Exibição de erros (só em desenvolvimento)
// ──────────────────────────────────────────────
if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// ──────────────────────────────────────────────
// Integrações externas
// ──────────────────────────────────────────────
define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: '');

// ──────────────────────────────────────────────
// Timezone e locale
// ──────────────────────────────────────────────
date_default_timezone_set('America/Sao_Paulo');
setlocale(LC_MONETARY, 'pt_BR.UTF-8', 'pt_BR', 'portuguese');

// ──────────────────────────────────────────────
// Segurança de sessão
// ──────────────────────────────────────────────
ini_set('session.cookie_httponly',  '1');
ini_set('session.use_strict_mode',  '1');
ini_set('session.gc_maxlifetime',   '7200'); // 2 horas de inatividade
ini_set('session.cookie_lifetime',  '7200'); // cookie persiste 2h mesmo após fechar o browser
ini_set('session.cookie_samesite',  'Strict');
// Ativar apenas em HTTPS em produção:
// ini_set('session.cookie_secure', '1');
