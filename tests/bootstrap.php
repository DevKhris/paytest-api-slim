<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

if (!isset($_ENV['APP_ENV'])) {
    $_ENV['APP_ENV'] = 'testing';
}
if (!isset($_ENV['APP_DEBUG'])) {
    $_ENV['APP_DEBUG'] = 'true';
}

date_default_timezone_set('UTC');
