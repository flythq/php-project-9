<?php

declare(strict_types=1);

namespace Hexlet\Code\Handlers;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Handlers\ErrorHandler;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Views\PhpRenderer;

class HttpErrorHandler extends ErrorHandler
{
    public function __construct(
        CallableResolverInterface $callableResolver,
        ResponseFactoryInterface $responseFactory
    ) {
        parent::__construct($callableResolver, $responseFactory);
    }

    protected function respond(): ResponseInterface
    {
        $statusCode = $this->statusCode ?: 500;

        $template = match ($statusCode) {
            404 => '404.phtml',
            default => '500.phtml',
        };

        $response = $this->responseFactory->createResponse($statusCode);

        $errorRenderer = new PhpRenderer(__DIR__ . '/../../templates/errors');

        return $errorRenderer->render($response, $template);
    }
}
