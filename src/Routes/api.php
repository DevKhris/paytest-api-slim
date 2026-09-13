<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    $app->get('/health', function (Request $request, Response $response): Response {
        $payload = json_encode(['message' => 'API is running']);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    });
};
