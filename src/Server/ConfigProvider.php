<?php

declare(strict_types=1);

namespace Marshal\Server;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            "events" => $this->getEventsConfig(),
            "layouts" => $this->getTemplateLayouts(),
            "middleware_pipeline" => $this->getMiddlewarePipeline(),
            "navigation" => $this->getRoutesConfig(),
            "templates" => $this->getTemplates(),
        ];
    }

    private function getDependencies(): array
    {
        return [
            'factories' => [
                Handler\HomeHandler::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
                Listener\ServerEventsListener::class => Listener\ServerEventsListenerFactory::class,
                Runtime\Apache2Handler\Apache2Handler::class => Runtime\Apache2Handler\Apache2HandlerFactory::class,
                Runtime\Cli\CliRuntime::class => Runtime\Cli\CliRuntimeFactory::class,
            ],
        ];
    }

    private function getEventsConfig(): array
    {
        return [
            'listeners' => [
                Listener\ServerEventsListener::class => [
                    \Marshal\Server\Event\HttpRequestEvent::class => [
                        'listener' => 'onHttpRequestEvent',
                    ],
                ],
            ],
        ];
    }

    private function getMiddlewarePipeline(): array
    {
        return [
            \Marshal\Platform\DetectPlatformMiddleware::class,
            \PSR7Sessions\Storageless\Http\SessionMiddleware::class,
            \Marshal\Authentication\AuthenticationMiddleware::class,
            \Mezzio\Router\Middleware\RouteMiddleware::class,
            \Mezzio\Router\Middleware\MethodNotAllowedMiddleware::class,
            \Mezzio\Router\Middleware\DispatchMiddleware::class,
            \Marshal\Platform\Web\Page\PageMiddleware::class,
            Handler\PageNotFoundHandler::class,
        ];
    }

    private function getRoutesConfig(): array
    {
        return [
            "paths" => [
                "/" => [
                    "methods" => ["GET"],
                    "middleware" => Handler\HomeHandler::class,
                    "name" => Handler\HomeHandler::ROUTE_NAME,
                ],
            ],
        ];
    }

    private function getTemplates(): array
    {
        return [
            "marshal::error-404" => [
                "filename" => __DIR__ . "/../../template/main/error-404.twig.html",
            ],
            "main::layout" => [
                "filename" => __DIR__ . "/../../template/main/layout.twig.html",
            ],
            Handler\HomeHandler::TEMPLATE_HOME => [
                "filename" => __DIR__ . "/../../template/main/home.twig.html",
                "includes" => ["main::layout"],
            ],
            "marshal::home-alternate" => [
                "layout" => "marshal::main",
            ],
        ];
    }

    private function getTemplateLayouts(): array
    {
        return [
            "marshal::empty" => [],
            "marshal::main" => [
                "filename" => __DIR__ . "/../../template/main/home.html",
            ],
        ];
    }
}
