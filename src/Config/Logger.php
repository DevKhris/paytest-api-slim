<?php

namespace PayTest\Config;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;

class Logger
{
    private static ?MonologLogger $instance = null;

    public static function getInstance(): MonologLogger
    {
        if (self::$instance === null) {
            self::$instance = new MonologLogger('paytest');
            
            $streamHandler = new StreamHandler(
                $_ENV['LOG_PATH'] ?? './logs/app.log',
                $_ENV['LOG_LEVEL'] ?? MonologLogger::DEBUG
            );
            $streamHandler->setFormatter(new JsonFormatter());
            
            self::$instance->pushHandler($streamHandler);
        }
        
        return self::$instance;
    }

    public static function info(string $message, array $context = []): void
    {
        self::getInstance()->info($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::getInstance()->error($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::getInstance()->warning($message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::getInstance()->debug($message, $context);
    }
}
