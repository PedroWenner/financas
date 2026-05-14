<?php
/**
 * Configurações de conexão com o banco de dados.
 *
 * IMPORTANTE: Não versionar este arquivo com credenciais reais.
 * Copie este arquivo para database.local.php e configure lá,
 * ou use variáveis de ambiente.
 */

declare(strict_types=1);

define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_PORT',    getenv('DB_PORT')    ?: '3306');
define('DB_NAME',    getenv('DB_NAME')    ?: 'alcatech_financas');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', 'utf8mb4');
