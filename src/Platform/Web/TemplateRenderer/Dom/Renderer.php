<?php

declare(strict_types=1);

namespace Marshal\Platform\Web\TemplateRenderer\Dom;

use Marshal\Platform\Web\TemplateRenderer\TemplateRendererInterface;

final class Renderer implements TemplateRendererInterface
{
    public function __construct(private array $templatesConfig, private array $layoutsConfig)
    {
    }

    public function render(string $template, iterable $data = [], array $options = []): string
    {
        if (! isset($this->templatesConfig[$template])) {
            throw new \InvalidArgumentException("Template $template not found");
        }

        $config = $this->templatesConfig[$template];
        // return \file_get_contents($config['filename']);
        $doc = $this->getLayout()->render($config['layout'] ?? "marshal::empty");
        // var_dump($doc);
        // foreach ($data['signals'] ?? [] as $dataKey => $dataValue) {
        //     $node = new Node($doc, $dataKey, $dataValue);
        //     $nodeDom = $node->render();
        //     $body = $doc->querySelector('body');
        //     $body->appendChild($nodeDom);
        // }

        return $doc->saveHtml();
    }

    private function getLayout(): Layout
    {
        return new Layout($this->layoutsConfig);
    }
}
