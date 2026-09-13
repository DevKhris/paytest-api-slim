<?php

use DI\ContainerBuilder;

require __DIR__ . '/../vendor/autoload.php';

$builder = new ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/../src/Config/Dependencies.php');
$container = $builder->build();

$app = \Slim\Factory\AppFactory::createFromContainer($container);
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

(require __DIR__ . '/../src/Routes/api.php')($app);

$app->run();
