<?php

namespace PayTest\Exceptions;

use PayTest\Config\Logger;
use PayTest\DTOs\Response\ApiResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Handlers\ErrorHandler;

class AppErrorHandler extends ErrorHandler
{
    protected function respond(iterable $response): ResponseInterface
    {
        $exception = $this->exception;

        if ($exception instanceof AppException) {
            Logger::warning('Application exception', [
                'message' => $exception->getMessage(),
                'status_code' => $exception->getStatusCode()
            ]);

            $body = json_encode(['error' => $exception->getMessage()]);
            $this->response->getBody()->write($body);

            return $this->response
                ->withStatus($exception->getStatusCode())
                ->withHeader('Content-Type', 'application/json');
        }

        Logger::error('Unhandled exception', [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        $message = $_ENV['APP_DEBUG'] ? $exception->getMessage() : 'Internal server error';
        $body = json_encode(['error' => $message]);
        $this->response->getBody()->write($body);

        return $this->response
            ->withStatus(500)
            ->withHeader('Content-Type', 'application/json');
    }
}
