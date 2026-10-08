<?php

declare(strict_types=1);

namespace Marshal\Platform\Web\TemplateRenderer\Dom;

final class Layout
{
    public function __construct(private array $config)
    {
    }

    public function render(string $layout): \Dom\HTMLDocument
    {
        if (! isset($this->config[$layout])) {
            throw new \InvalidArgumentException("Layout $layout not found in config");
        }

        $doc = \Dom\HTMLDocument::createFromString(\file_get_contents($this->config[$layout]['filename']));
        // $layout = $this->config[$layout];
        // if (isset($layout['head'])) {
        //     $head = $doc->createElement('head');
        //     foreach ($layout['head']['meta'] ?? [] as $key => $value) {
        //         $meta = $doc->createElement('meta');
        //         $meta->setAttribute($key, $value);
        //         $head->appendChild($meta);
        //     }
        //     $doc->appendChild($head);
        // }

        return $doc;
    }
}
