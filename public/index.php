<?php

use DI\ContainerBuilder;
use PayTest\Exceptions\AppErrorHandler;

require __DIR__ . '/../vendor/autoload.php';

$builder = new ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/../src/Config/Dependencies.php');
$container = $builder->build();

$app = \Slim\Factory\AppFactory::createFromContainer($container);
$app->addRoutingMiddleware();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setDefaultErrorHandler(
    new AppErrorHandler($app->getCallableResolver(), $app->getResponseFactory())
);

(require __DIR__ . '/../src/Routes/api.php')($app);

$app->run();
