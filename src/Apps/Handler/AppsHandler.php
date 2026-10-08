<?php

declare(strict_types=1);

namespace Marshal\Apps\Handler;

use Marshal\Apps\App;
use Marshal\Platform\PlatformInterface;
use Marshal\Utils\Helper\ServerRequestHelperTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AppsHandler implements RequestHandlerInterface
{
    use ServerRequestHelperTrait;

    public const string APPS_DASHBOARD = "marshal::apps-dashboard";
    public const string APP_DASHBOARD = "marshal::app-dashboard";

    public function __construct(private ContainerInterface $container, private array $appsConfig)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $platform = $this->getRequestPlatform($request);
        $routeResult = $this->getRouteResult($request);
        return match ($routeResult->getMatchedRouteName()) {
            self::APPS_DASHBOARD => $this->handleAppsDashboard($request, $platform),
            self::APP_DASHBOARD => $this->handleAppDashboard($request, $platform),
            default => $platform->badRequestResponse($request)
        };
    }

    private function appTagExists(string $tag): ?array
    {
        foreach ($this->appsConfig as $config) {
            if (isset($config['tag']) && $tag === $config['tag']) {
                return $config;
            }
        }

        return null;
    }

    private function handleAppDashboard(ServerRequestInterface $request, PlatformInterface $platform): ResponseInterface
    {
        $appConfig = $this->appTagExists($request->getAttribute('app'));
        if (! $appConfig) {
            return $platform->notFoundResponse($request);
        }

        $app = new App($appConfig);

        return $platform->formatResponse($request, [
            "app" => $app->toArray(),
        ], self::APP_DASHBOARD);
    }

    private function handleAppsDashboard(ServerRequestInterface $request, PlatformInterface $platform): ResponseInterface
    {
        $apps = [];
        foreach ($this->appsConfig as $app => $config) {
            if ($app === "marshal::default") {
                continue;
            }

            $apps[$app] = [
                "label" => $config["label"],
                "description" => $config["description"],
                "tag" => $config["tag"],
            ];
        }
        return $platform->formatResponse($request, [
            'apps' => $apps,
        ], self::APPS_DASHBOARD);
    }
}
