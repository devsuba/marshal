<?php

declare(strict_types=1);

namespace Marshal\Server\Handler;

use Marshal\Utils\Helper\ServerRequestHelperTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class PageNotFoundHandler implements RequestHandlerInterface
{
    use ServerRequestHelperTrait;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->getRequestPlatform($request)->notFoundResponse($request);
    }
}

