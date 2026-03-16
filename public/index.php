<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require_once __DIR__ . '/../vendor/autoload.php';

$container = new Container();

$container->set(Twig::class, function () {
    return Twig::create(__DIR__ . '/../templates', ['cache' => false]);
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);

$app->add(TwigMiddleware::create($app, $container->get(Twig::class)));

$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);



$app->get('/', function ($request, $response) use ($container) {

    $twig = $container->get(Twig::class);

    return $twig->render($response, 'layout.html.twig');
});

$app->run();
