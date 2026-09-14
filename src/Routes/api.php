<?php

use PayTest\Controllers\AuthController;
use PayTest\Controllers\AccountController;
use PayTest\Controllers\TransactionController;
use PayTest\Controllers\ContactController;
use PayTest\Middleware\JwtAuthMiddleware;
use PayTest\Middleware\RequestLoggerMiddleware;
use Slim\App;

return function (App $app) {
    $app->add(RequestLoggerMiddleware::class);

    // Public routes
    $app->post('/auth/room-code', [AuthController::class, 'validateRoomCode']);
    $app->post('/auth/register', [AuthController::class, 'register']);
    $app->post('/auth/login', [AuthController::class, 'login']);

    // Protected routes
    $app->group('', function ($group) {
        $group->post('/auth/logout', [AuthController::class, 'logout']);

        // Account
        $group->get('/accounts/balance', [AccountController::class, 'getBalance']);

        // Transactions
        $group->get('/transactions', [TransactionController::class, 'getTransactions']);
        $group->post('/transactions/transfer', [TransactionController::class, 'transfer']);

        // Contacts
        $group->get('/contacts', [ContactController::class, 'listContacts']);
        $group->post('/contacts', [ContactController::class, 'addContact']);
        $group->delete('/contacts/{id}', [ContactController::class, 'deleteContact']);
    })->add(JwtAuthMiddleware::class);
};
