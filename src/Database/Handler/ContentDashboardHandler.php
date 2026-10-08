<?php

declare(strict_types=1);

namespace Marshal\Database\Handler;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Marshal\Utils\Helper\ServerRequestHelperTrait;
use Marshal\Utils\Config;

final class ContentDashboardHandler implements RequestHandlerInterface
{
    use ContentHandlerTrait;
    use ServerRequestHelperTrait;

    public const string ROUTE_CONTENT_DASHBOARD = "marshal::content-dashboard";

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $schemas = [];
        $config = Config::get('database');
        foreach ($config ?? [] as $database) {
            if (isset($database['system']) && true === $database['system']) {
                continue;
            }

            if (! isset($database['tag'])) {
                continue;
            }

            $schemas[$database['tag']] = [
                'name' => $database['label'],
            ];
        }

        $platform = $this->getRequestPlatform($request);
        return $platform->formatResponse($request, [
            'data' => $schemas,
        ], self::ROUTE_CONTENT_DASHBOARD);
    }
}
