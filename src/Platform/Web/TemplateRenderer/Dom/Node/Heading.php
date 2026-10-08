<?php

declare(strict_types=1);

namespace Marshal\Platform\Web\TemplateRenderer\Dom\Node;

final class Heading
{
    public function render(\Dom\HTMLDocument $doc, string $data): \Dom\Node
    {
        $heading = $doc->createElement("h1");
        $heading->textContent = $data;
        return $heading;
    }
}
