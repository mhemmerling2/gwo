<?php

declare(strict_types=1);

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (is_string($requestPath) && $requestPath !== '/') {
    $publicFile = __DIR__.'/../public'.$requestPath;

    if (is_file($publicFile)) {
        return false;
    }
}

require __DIR__.'/../public/index.php';
