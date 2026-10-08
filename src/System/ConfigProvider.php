<?php

declare(strict_types=1);

namespace Marshal\System;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            "dependencies" => $this->getDependencies(),
            "navigation" => $this->getRoutes(),
            "templates" => $this->getTemplates(),
        ];
    }

    private function getDependencies(): array
    {
        return [
            "factories" => [
                Handler\SystemHandler::class            => \Laminas\ServiceManager\Factory\InvokableFactory::class,
            ],
        ];
    }

    private function getRoutes(): array
    {
        return [
            "paths" => [
                "/system" => [
                    "methods" => ["GET"],
                    "middleware" => Handler\SystemHandler::class,
                    "name" => Handler\SystemHandler::SYSTEM_PAGE,
                ],
            ],
        ];
    }

    private function getTemplates(): array
    {
        return [
            Handler\SystemHandler::SYSTEM_PAGE => [
                "filename" => __DIR__ . "/../../template/system/dashboard.twig.html",
                "includes" => ["main::layout"],
            ],
        ];
    }
}
