<?php

declare(strict_types=1);

namespace Marshal\Apps;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            "apps" => $this->getDefaultAppConfig(),
            "dependencies" => $this->getDependencies(),
            "navigation" => $this->getRoutes(),
            "templates" => $this->getTemplates(),
        ];
    }

    private function getDefaultAppConfig(): array
    {
        return [
            "marshal::default" => [
                "label" => "Marshal",
                "description" => "Default App",
                "tag" => "marshal",
            ],
        ];
    }

    private function getDependencies(): array
    {
        return [
            "factories" => [
                Handler\AppsHandler::class => Handler\AppsHandlerFactory::class,
            ],
        ];
    }

    private function getRoutes(): array
    {
        return [
            "paths" => [
                "/apps" => [
                    "name" => Handler\AppsHandler::APPS_DASHBOARD,
                    "methods" => ["GET"],
                    "middleware" => Handler\AppsHandler::class,
                ],
                "/apps/{app}" => [
                    "name" => Handler\AppsHandler::APP_DASHBOARD,
                    "methods" => ["GET"],
                    "middleware" => Handler\AppsHandler::class,
                ],
            ],
        ];
    }

    private function getTemplates(): array
    {
        return [
            Handler\AppsHandler::APPS_DASHBOARD => [
                "filename" => __DIR__ . "/../../template/apps/apps-dashboard.twig.html",
                "includes" => ["main::layout"],
            ],
            Handler\AppsHandler::APP_DASHBOARD => [
                "filename" => __DIR__ . "/../../template/apps/app-dashboard.twig.html",
                "includes" => ["main::layout"],
            ],
        ];
    }
}
