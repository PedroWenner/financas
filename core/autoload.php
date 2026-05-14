<?php
/**
 * Autoloader simples para carregar Controllers e Models automaticamente.
 */

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $paths = [
        APP_PATH  . '/Controllers/' . $class . '.php',
        APP_PATH  . '/Models/'      . $class . '.php',
        APP_PATH  . '/Services/'    . $class . '.php',
        CORE_PATH . '/'             . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});
