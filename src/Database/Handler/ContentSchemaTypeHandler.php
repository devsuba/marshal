<?php

declare(strict_types=1);

namespace Marshal\Database\Handler;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Marshal\Utils\Helper\ServerRequestHelperTrait;

final class ContentSchemaTypeHandler implements RequestHandlerInterface
{
    use ServerRequestHelperTrait;

    public const string ROUTE_CONTENT_SCHEMA_TYPE = "marshal::content-schema-type";

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $platform = $this->getRequestPlatform($request);
        return $platform->formatResponse($request, [
            'data' => [],
        ], self::ROUTE_CONTENT_SCHEMA_TYPE);
    }
}
