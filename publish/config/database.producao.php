<?php
/**
 * AlcatechFinancas — Credenciais do Banco de PRODUCAO
 * Substitui config/database.php na Hostinger.
 *
 * COMO ENCONTRAR ESSES DADOS NA HOSTINGER:
 *   hPanel > Bancos de Dados MySQL > clique no banco criado
 *   As credenciais aparecem na tela de detalhes.
 *
 * FORMATO DO NOME DO BANCO E USUARIO NA HOSTINGER:
 *   Banco:   u123456789_financas   (prefixo do usuario + nome)
 *   Usuario: u123456789_user       (prefixo do usuario + nome)
 *
 * As credenciais reais devem vir de variaveis de ambiente (.env no servidor
 * ou SetEnv no .htaccess). Nunca coloque senhas reais neste arquivo.
 */

declare(strict_types=1);

define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_PORT',    getenv('DB_PORT')    ?: '3306');
define('DB_NAME',    getenv('DB_NAME')    ?: '');
define('DB_USER',    getenv('DB_USER')    ?: '');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', 'utf8mb4');
