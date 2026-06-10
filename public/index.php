<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Dotenv\Dotenv;
use Hexlet\Code\Services\PageAnalyzer;
use Illuminate\Support\Carbon;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\PhpRenderer;
use Valitron\Validator;
use Hexlet\Code\Support\StringUtils;
use Hexlet\Code\Support\Database;
use Hexlet\Code\Support\UrlRepository;
use Hexlet\Code\Support\UrlCheckRepository;
use Hexlet\Code\Support\TableCreator;
use Hexlet\Code\Support\HttpErrorHandler;
use GuzzleHttp\Exception\GuzzleException;


$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();
$dotenv->required(['DATABASE_URL'])->notEmpty();

try {
    $conn = Database::connect();
    TableCreator::run($conn);

    session_start();

    $container = new Container();
    $container->set('renderer', function () {
        $render = new PhpRenderer(__DIR__ . '/../templates');
        $render->setLayout('layout.phtml');
        return $render;
    });
    $container->set(PDO::class, fn() => $conn);
    $container->set('flash', function () {
        return new Messages();
    });

    $app = AppFactory::createFromContainer($container);

    $callableResolver = $app->getCallableResolver();
    $responseFactory = $app->getResponseFactory();

    $httpErrorHandler = new HttpErrorHandler(
        $callableResolver,
        $responseFactory,
    );

    $app->addRoutingMiddleware();

    $errorMiddleware = $app->addErrorMiddleware(false, true, true);

    $errorMiddleware->setDefaultErrorHandler($httpErrorHandler);


    $app->add(MethodOverrideMiddleware::class);

    $router = $app->getRouteCollector()->getRouteParser();

    $container->set('router', fn() => $router);
    $container->set(UrlRepository::class, function ($container) {
        return new UrlRepository($container->get(PDO::class));
    });
    $container->set(UrlCheckRepository::class, function ($container) {
        return new UrlCheckRepository($container->get(PDO::class));
    });

    $app->add(function ($request, $handler) use ($container, $router) {
        $renderer = $container->get('renderer');

        $renderer->addAttribute(
            'flash',
            $container->get('flash')->getMessages()
        );

        $renderer->addAttribute('router', $router);

        $path = $request->getUri()->getPath();
        $renderer->addAttribute('currentPath', $path);

        return $handler->handle($request);
    });


    $app->get('/', function ($request, $response) use ($container) {
        return $container->get('renderer')->render($response, 'pages/index.phtml');
    })->setName('index');

    $app->get('/urls', function ($request, $response) use ($container) {
        $urls = $container->get(UrlRepository::class)->getAll();

        $checks = $container->get(UrlCheckRepository::class)->getLastChecks();

        $checksByUrlId = [];

        foreach ($checks as $check) {
            $checksByUrlId[$check['url_id']] = $check;
        }

        $params = [
            'urls'          => $urls,
            'checksByUrlId' => $checksByUrlId,
        ];

        return $container->get('renderer')->render($response, 'pages/urls/index.phtml', $params);
    })->setName('urls.index');

    $app->post('/urls', function ($request, $response) use ($container) {
        $params = $request->getParsedBody();
        $urlName = trim($params['url']);

        $validator = new Validator(['url' => $urlName]);
        $validator->rule('required', 'url')
            ->message('URL не должен быть пустым');
        $validator->rule('url', 'url')
            ->message('Некорректный URL');
        $validator->rule('lengthMax', 'url', 255)
            ->message('URL не должен превышать 255 символов');

        if (!$validator->validate()) {
            $errors = $validator->errors('url');
            $error = $errors[0] ?? null;
            return $container->get('renderer')->render(
                $response,
                'pages/index.phtml',
                ['error' => $error]
            )->withStatus(422);
        }

        $parsedUrl = parse_url($urlName);

        if (
            $parsedUrl === false ||
            !isset($parsedUrl['scheme'], $parsedUrl['host'])
        ) {
            throw new InvalidArgumentException('Invalid URL');
        }

        $normalizedUrl = "{$parsedUrl['scheme']}://{$parsedUrl['host']}";
        $urlData = $container->get(UrlRepository::class)->getByName($normalizedUrl);

        if (!empty($urlData)) {
            $redirectUrl = $container->get('router')->urlFor('urls.show', ['id' => (string)$urlData['id']]);
            $container->get('flash')->addMessage('success', 'Страница уже существует');
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        }

        $id = $container->get(UrlRepository::class)->create(
            $normalizedUrl,
            Carbon::now()->toDateTimeString()
        );

        $redirectUrl = $container->get('router')->urlFor('urls.show', ['id' => (string)$id]);
        $container->get('flash')->addMessage('success', 'Страница успешно добавлена');
        return $response->withHeader('Location', $redirectUrl)->withStatus(302);
    })->setName('urls.create');

    $app->get('/urls/{id:[0-9]+}', function ($request, $response, $args) use ($container) {
        $urlId = (int)$args['id'];
        $urlData = $container->get(UrlRepository::class)->getById($urlId);

        if (!$urlData) {
            throw new HttpNotFoundException($request);
        }

        $urlCheckData = $container->get(UrlCheckRepository::class)->getByUrlId($urlId);

        $urlCheckData = array_map(function ($check) {
            $check['h1'] = StringUtils::preview($check['h1']);
            $check['title'] = StringUtils::preview($check['title']);
            $check['description'] = StringUtils::preview($check['description']);

            return $check;
        }, $urlCheckData);

        $data = [
            'urlData'      => $urlData,
            'urlCheckData' => $urlCheckData,
        ];

        return $container->get('renderer')->render($response, 'pages/urls/show.phtml', $data);
    })->setName('urls.show');

    $app->post('/urls/{id:[0-9]+}/checks', function ($request, $response, $args) use ($container) {
        $urlId = (int)$args['id'];
        $url = $container->get(UrlRepository::class)->getById($urlId);

        if (!$url) {
            throw new InvalidArgumentException("UrlRepository with id {$urlId} not found");
        }

        try {
            $data = PageAnalyzer::analyze($url['name']);

            $container->get(UrlCheckRepository::class)->create(
                $urlId,
                $data['statusCode'],
                $data['h1'],
                $data['title'],
                $data['description'],
                Carbon::now()->toDateTimeString()
            );

            $container->get('flash')->addMessage('success', 'Страница успешно проверена');
        } catch (GuzzleException $e) {
            $container->get('flash')->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
        }

        $redirectUrl = $container->get('router')->urlFor('urls.show', ['id' => (string)$urlId]);

        return $response->withHeader('Location', $redirectUrl)->withStatus(302);
    })->setName('urls.check');

    $app->run();
} catch (Throwable $e) {
    http_response_code(500);

    $renderer = new PhpRenderer(__DIR__ . '/../templates');
    echo $renderer->fetch('errors/500.phtml');
    exit;
}
