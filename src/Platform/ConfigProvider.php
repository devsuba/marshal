<?php

declare(strict_types=1);

namespace Marshal\Platform;

use Marshal\Database\Schema\Content;
use Marshal\Platform\Web\Page\Page;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            "dependencies" => $this->getDependencies(),
            "events" => $this->getEventsConfig(),
            "schema" => $this->getSchemaConfig(),
            "twig" => $this->getTwigConfig(),
        ];
    }

    private function getDependencies(): array
    {
        return [
            "factories" => [
                API\APIPlatform::class                                          => API\APIPlatformFactory::class,
                DetectPlatformMiddleware::class                                 => DetectPlatformMiddlewareFactory::class,
                Web\Page\PageMiddleware::class                                  => \Laminas\ServiceManager\Factory\InvokableFactory::class,
                Web\WebPlatform::class                                          => Web\WebPlatformFactory::class,
                Web\TemplateRenderer\TemplateRendererResolverInterface::class   => Web\TemplateRenderer\TempateRendererResolverFactory::class,
                Web\TemplateRenderer\Twig\RuntimeLoader::class                  => Web\TemplateRenderer\Twig\RuntimeLoaderFactory::class,
                Web\TemplateRenderer\Twig\TwigTemplateRenderer::class           => Web\TemplateRenderer\Twig\TwigTemplateRendererFactory::class,
            ],
        ];
    }

    private function getEventsConfig(): array
    {
        return [
            'listeners' => [],
        ];
    }

    private function getSchemaConfig(): array
    {
        return [
            "properties" => [
                Page::BODY => [],
                Page::LAYOUT => [],
                Page::META => [],
                Page::TITLE => [],
            ],
            "types" => [
                Web\Page\Page::class => [
                    "properties" => [
                        Content::ID,
                        Page::BODY,
                        Page::LAYOUT,
                        Page::META,
                        Page::TITLE,
                        Content::DESCRIPTION,
                        Content::IMAGE,
                        Content::TAG,
                        Content::CREATED_AT,
                        Content::UPDATED_AT,
                    ],
                ],
            ]
        ];
    }

    private function getTwigConfig(): array
    {
        return [
            "runtime_loaders" => [
                Web\TemplateRenderer\Twig\RuntimeLoader::class,
            ],
            "functions" => [
                [
                    "name" => "contentForm",
                    "callable" => [Web\TemplateRenderer\Twig\FormExtension::class, "contentForm"],
                ],
                [
                    "name" => "form",
                    "callable" => [Web\TemplateRenderer\Twig\FormExtension::class, "form"],
                ],
                [
                    "name" => "media",
                    "callable" => [Web\TemplateRenderer\Twig\UrlExtension::class, "media"],
                    "options" => [
                        "needs_context" => true,
                    ],
                ],
                [
                    "name" => "path",
                    "callable" => [Web\TemplateRenderer\Twig\UrlExtension::class, "path"],
                ],
                [
                    "name" => "static",
                    "callable" => [Web\TemplateRenderer\Twig\UrlExtension::class, "static"],
                    "options" => [
                        "needs_context" => true,
                        "needs_environment" => true,
                    ],
                ],
            ],
        ];
    }
}
