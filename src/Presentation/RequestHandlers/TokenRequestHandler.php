<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Route;
use Presentation\Middlewares\ViewMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Presentation\Response\ViewResponse;

#[Middleware(ViewMiddleware::class)]
#[Route(path: '/tua', method: RequestMethod::GET)]
class TokenRequestHandler extends AbstractRequestHandler implements
    RequestHandlerInterface
{
    /**
     * @param ServerRequestInterface $request 
     * @return ResponseInterface 
     */
    public function handle(
        ServerRequestInterface $request
    ): ResponseInterface {
        return new ViewResponse(
            'templates/token.twig'
        );
    }
}
