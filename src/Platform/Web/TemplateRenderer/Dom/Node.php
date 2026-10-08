<?php

declare(strict_types=1);

namespace Marshal\Platform\Web\TemplateRenderer\Dom;

use Marshal\Platform\Web\TemplateRenderer\Dom\Node\Heading;

final class Node
{
    public function __construct(private \Dom\HTMLDocument $doc, private string $key, private mixed $data)
    {
    }

    public function render(): \Dom\Node
    {
        $node = match ($this->key) {
            default => new Heading(),
        };

        return $node->render($this->doc, $this->data);
    }
}
