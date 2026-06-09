<?php

declare(strict_types=1);

namespace Hexlet\Code\Support;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\ErrorHandler;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Views\PhpRenderer;

class HttpErrorHandler extends ErrorHandler
{
    private PhpRenderer $view;

    public function __construct(
        CallableResolverInterface $callableResolver,
        ResponseFactoryInterface $responseFactory,
        PhpRenderer $view
    ) {
        $this->view = $view;

        parent::__construct($callableResolver, $responseFactory);
    }

    protected function respond(): ResponseInterface
    {
        $exception = $this->exception;

        $statusCode = 500;
        $template = '500.phtml';

        if ($exception instanceof HttpNotFoundException) {
            $statusCode = 404;
            $template = '404.phtml';
        }

        $response = $this->responseFactory->createResponse($statusCode);

        return $this->view->render($response, $template);
    }
}
