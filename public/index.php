<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Dotenv\Dotenv;
use Hexlet\Code\Services\PageAnalyzer;
use Illuminate\Support\Carbon;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\PhpRenderer;
use Valitron\Validator;
use Hexlet\Code\Support\Text;
use Hexlet\Code\Support\Database;
use Hexlet\Code\Support\Url;
use Hexlet\Code\Support\UrlCheck;


$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();
$dotenv->required(['DATABASE_URL'])->notEmpty();

$conn = Database::connect();
\Hexlet\Code\Support\TableCreator::run($conn);

session_start();

$container = new Container();
$container->set('flash', function () {
    return new Messages();
});
$container->set('renderer', function () {
    return new PhpRenderer(__DIR__ . '/../templates');
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) use ($container) {
    $messages = $container->get('flash')->getMessages();
    $params = [
        'flash' => $messages ?? []
    ];
    return $container->get('renderer')->render($response, 'index.phtml', $params);
})->setName('index');

$app->get('/urls', function ($request, $response) use ($conn, $container) {
    $urlsData = Url::getAll($conn);
    $params = ['urlsData' => $urlsData];

    return $container->get('renderer')->render($response, 'urls.phtml', $params);
})->setName('urls.index');

$app->post('/urls', function ($request, $response) use ($router, $conn, $container) {
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
        return $container->get('renderer')->render($response, 'index.phtml', ['error' => $error])->withStatus(422);
    }

    $parsedUrl = parse_url($urlName);

    if (
        $parsedUrl === false ||
        !isset($parsedUrl['scheme'], $parsedUrl['host'])
    ) {
        throw new InvalidArgumentException('Invalid URL');
    }

    $normalizedUrl = "{$parsedUrl['scheme']}://{$parsedUrl['host']}";
    $urlData = Url::getByName($conn, $normalizedUrl);

    if (!empty($urlData)) {
        $redirectUrl = $router->urlFor('urls.show', ['id' => (string) $urlData['id']]);
        $container->get('flash')->addMessage('success', 'Страница уже существует');
        return $response->withHeader('Location', $redirectUrl)->withStatus(302);
    }

    $id = Url::create(
        $conn,
        $normalizedUrl,
        Carbon::now()->toDateTimeString()
    );

    $redirectUrl = $router->urlFor('urls.show', ['id' => (string) $id]);
    $container->get('flash')->addMessage('success', 'Страница успешно добавлена');
    return $response->withHeader('Location', $redirectUrl)->withStatus(302);
})->setName('urls.create');

$app->get('/urls/{id}', function ($request, $response, $args) use ($conn, $container) {
    $urlId = (int)$args['id'];
    $urlData = Url::getById($conn, $urlId);

    $urlCheckData = UrlCheck::getByUrlId($conn, $urlId);

    $urlCheckData = array_map(function ($check) {
        $check['h1'] = Text::preview($check['h1']);
        $check['title'] = Text::preview($check['title']);
        $check['description'] = Text::preview($check['description']);

        return $check;
    }, $urlCheckData);

    $flash = $container->get('flash')->getMessages();


    $data = [
        'urlData' => $urlData,
        'urlCheckData' => $urlCheckData,
        'flash' => $flash,
    ];
    return $container->get('renderer')->render($response, 'url.phtml', $data);
})->setName('urls.show');

$app->post('/urls/{id:[0-9]+}/checks', function ($request, $response, $args) use ($router, $conn, $container) {
    $urlId = (int)$args['id'];
    $url = Url::getById($conn, $urlId);

    if (!$url) {
        throw new InvalidArgumentException("Url with id {$urlId} not found");
    }

    try {
        $data = PageAnalyzer::analyze($url['name']);

        UrlCheck::create(
            $conn,
            $urlId,
            $data['statusCode'],
            $data['h1'],
            $data['title'],
            $data['description'],
            Carbon::now()->toDateTimeString()
        );

        $container->get('flash')->addMessage('success', 'Страница успешно проверена');
    } catch (\GuzzleHttp\Exception\GuzzleException $e) {
        $container->get('flash')->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
    }

    $redirectUrl = $router->urlFor('urls.show', ['id' => (string) $urlId]);

    return $response->withHeader('Location', $redirectUrl)->withStatus(302);
})->setName('urls.check');

$app->run();

return $app;
