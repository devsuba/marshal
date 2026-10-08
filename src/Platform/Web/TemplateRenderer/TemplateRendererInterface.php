<?php

declare(strict_types=1);

namespace Marshal\Platform\Web\TemplateRenderer;

interface TemplateRendererInterface
{
    public function render(string $template, iterable $data = [], array $options = []): string;
}
