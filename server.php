<?php

$publicPath = __DIR__ . '/public';

if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    if (is_file($publicPath . $path)) {
        return false;
    }
}

require_once $publicPath . '/index.php';
