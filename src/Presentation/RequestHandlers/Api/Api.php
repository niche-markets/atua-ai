<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api;

use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Path;
use Presentation\Middlewares\AuthorizationMiddleware;
use Presentation\Middlewares\EmailVerificationMiddleware;
use Presentation\RequestHandlers\AbstractRequestHandler;

#[Middleware(AuthorizationMiddleware::class)]
#[Middleware(EmailVerificationMiddleware::class)]
#[Path('/api')]
abstract class Api extends AbstractRequestHandler
{
}
