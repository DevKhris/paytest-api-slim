<?php

use PayTest\Controllers\AuthController;
use PayTest\Controllers\AccountController;
use PayTest\Controllers\TransactionController;
use PayTest\Controllers\ContactController;
use PayTest\Controllers\SessionController;
use PayTest\Middleware\JwtAuthMiddleware;
use PayTest\Middleware\RequestLoggerMiddleware;
use Slim\App;

return function (App $app) {
    $app->add(RequestLoggerMiddleware::class);

    // Public routes
    $app->post('/auth/register', [AuthController::class, 'register']);
    $app->post('/auth/login', [AuthController::class, 'login']);
    $app->get('/auth/sala/{code}', [AuthController::class, 'validateSala']);

    // Protected routes
    $app->group('', function ($group) {
        // Account
        $group->get('/accounts/balance', [AccountController::class, 'getBalance']);

        // Transactions
        $group->post('/transactions/send', [TransactionController::class, 'sendMoney']);
        $group->get('/transactions/history', [TransactionController::class, 'getHistory']);

        // Contacts
        $group->post('/contacts/add', [ContactController::class, 'addContact']);
        $group->get('/contacts/list', [ContactController::class, 'listContacts']);

        // Sessions
        $group->get('/sessions/info', [SessionController::class, 'getSessionInfo']);
        $group->post('/sessions/logout', [SessionController::class, 'logout']);
    })->add(JwtAuthMiddleware::class);
};
