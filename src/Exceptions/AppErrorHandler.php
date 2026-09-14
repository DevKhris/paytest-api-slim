<?php

namespace PayTest\Exceptions;

use PayTest\Config\Logger;
use PayTest\DTOs\Response\ApiResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Handlers\ErrorHandler;

class AppErrorHandler extends ErrorHandler
{
    protected function respond(): ResponseInterface
    {
        $exception = $this->exception;

        if ($exception instanceof AppException) {
            Logger::warning('Application exception', [
                'message' => $exception->getMessage(),
                'status_code' => $exception->getStatusCode()
            ]);

            $response = $this->responseFactory->createResponse($exception->getStatusCode());
            $body = json_encode(['error' => $exception->getMessage()]);
            $response->getBody()->write($body);

            return $response->withHeader('Content-Type', 'application/json');
        }

        Logger::error('Unhandled exception', [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        $message = isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] ? $exception->getMessage() : 'Internal server error';
        $response = $this->responseFactory->createResponse(500);
        $body = json_encode(['error' => $message]);
        $response->getBody()->write($body);

        return $response->withHeader('Content-Type', 'application/json');
    }
}
