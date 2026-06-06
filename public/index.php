<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\PhpRenderer;
use Dotenv\Dotenv;
use Valitron\Validator;
use Illuminate\Support\Carbon;

const DEFAULT_DATABASE_PORT = '5432';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();
$dotenv->required(['DATABASE_URL'])->notEmpty();

$params = parse_url($_ENV['DATABASE_URL']);

if ($params === false) {
    throw new InvalidArgumentException("Error reading database url.");
}

$port = $params['port'] ?? DEFAULT_DATABASE_PORT;
$host = $params['host'] ?? '';
$user = $params['user'] ?? '';
$password = $params['pass'] ?? '';
$name = ltrim($params['path'] ?? '', '/');

$dsn = "pgsql:host={$host};port={$port};dbname={$name};sslmode=require";

try {
    $conn = new PDO(
        $dsn,
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception | PDOException $e) {
    throw new PDOException("Error database connection: " . $e->getMessage());
}

$dsn = null;

$databaseFilePath = dirname(__DIR__) . '/database.sql';
$sql = file_get_contents($databaseFilePath);

if ($sql === false) {
    throw new RuntimeException('Unable to read database file!');
}

$conn->exec($sql);

session_start();

$container = new Container();
$container->set(PDO::class, fn() => $conn);
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

$app->get('/', function ($request, $response) {
    $messages = $this->get('flash')->getMessages();
    $params = [
        'flash' => $messages ?? []
    ];
    return $this->get('renderer')->render($response, 'index.phtml', $params);
})->setName('index');

$app->get('/urls', function ($request, $response) use ($conn) {
    $sql = "SELECT * FROM urls ORDER BY created_at DESC";
    $stmt = $conn->query($sql);

    if ($stmt === false) {
        throw new RuntimeException('Failed to execute SQL query');
    }

    $urlsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $params = ['urlsData' => $urlsData];
    return $this->get('renderer')->render($response, 'urls.phtml', $params);
})->setName('urls.index');

$app->post('/urls', function ($request, $response) use ($router, $conn) {
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
        return $this->get('renderer')->render($response, 'index.phtml', ['error' => $error])->withStatus(422);
    }

    $parsedUrl = parse_url($urlName);
    $normalizedUrl = "{$parsedUrl['scheme']}://{$parsedUrl['host']}";
    $sql = "SELECT id, name, created_at FROM urls WHERE name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$normalizedUrl]);
    $urlData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!empty($urlData)) {
        $redirectUrl = $router->urlFor('urls.show', ['id' => $urlData['id']]);
        $this->get('flash')->addMessage('success', 'Страница уже существует');
        return $response->withHeader('Location', $redirectUrl)->withStatus(302);
    }

    $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)";
    $stmt = $conn->prepare($sql);
    $stmt->execute(
        [
            'name' => $normalizedUrl,
            'created_at' => Carbon::now()->toDateTimeString(),
        ]
    );
    $id = (int) $conn->lastInsertId();
    $redirectUrl = $router->urlFor('urls.show', ['id' => $id]);
    $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
    return $response->withHeader('Location', $redirectUrl)->withStatus(302);
})->setName('urls.create');

$app->get('/urls/{id}', function ($request, $response, $args) use ($conn) {
    $urlId = (int)$args['id'];
    $sql = "SELECT id, name, to_char(created_at, 'YYYY-MM-DD HH24:MI:SS TZ') 
            AS created_at
            FROM urls
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$urlId]);
    $urlData =  $stmt->fetch(PDO::FETCH_ASSOC);
    $flash = $this->get('flash')->getMessages();

    $data = [
        'urlData' => $urlData,
        'flash' => $flash
    ];
    return $this->get('renderer')->render($response, 'url.phtml', $data);
})->setName('urls.show');

$app->post('/urls/{id:[0-9]+}/checks', function ($request, $response, $args) use ($router, $conn) {
    $urlId = (int)$args['id'];
    $sql = "SELECT id, name, to_char(created_at, 'YYYY-MM-DD HH24:MI:SS TZ') as created_at
            FROM urls
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$urlId]);
    $url =  $stmt->fetch(PDO::FETCH_ASSOC);
    $redirectUrl = $router->urlFor('urls.show', ['id' => $urlId]);

    ($url === []) ?
        $this->get('flash')->addMessage('warning', 'Произошла ошибка при проверке, не удалось подключиться') :
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');

    return $response->withHeader('Location', $redirectUrl)->withStatus(302);
})->setName('urls.check');

$app->run();
