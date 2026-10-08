<?php

declare(strict_types=1);

namespace Marshal\Platform\Web\TemplateRenderer;

use Psr\Container\ContainerInterface;

final class TemplateRendererResolver implements TemplateRendererResolverInterface
{
    public function __construct(private ContainerInterface $container, private array $config)
    {
    }

    public function resolve(string $template): TemplateRendererInterface
    {
        if (! isset($this->config[$template])) {
            throw new \InvalidArgumentException("Template $template not found in config");
        }

        if (isset($this->config[$template]['filename']) && \str_contains($this->config[$template]['filename'], '.twig')) {
            return $this->container->get(Twig\TwigTemplateRenderer::class);
        }

        throw new \RuntimeException(\sprintf(
            "%s for template %s not found",
            TemplateRendererInterface::class, $template
        ));
    }
}
