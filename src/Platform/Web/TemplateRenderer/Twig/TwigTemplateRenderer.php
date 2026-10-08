<?php

declare(strict_types=1);

namespace Marshal\Platform\Web\TemplateRenderer\Twig;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Marshal\Platform\Web\TemplateRenderer\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\ArrayLoader;

class TwigTemplateRenderer implements TemplateRendererInterface
{
    public function __construct(
        private ContainerInterface $container,
        private array $config = [],
        private array $options = [],
        private array $templatesConfig = []
    ) {
    }

    public function render(string $template, iterable $data = [], array $options = []): string
    {
        $loaderData = [$template => $this->getTemplateContents($template)];
        foreach ($this->resolveTemplateIncludes($template) as $k) {
            $loaderData[$k] = $this->getTemplateContents($k);
        }

        // build the environment
        $twig = new Environment(new ArrayLoader($loaderData), $this->options);

        // add necessary extensions
        $twig->addExtension(new TwigExtension($this->config));

        // add optional extensions
        if (isset($this->options['debug']) && TRUE === $this->options['debug']) {
            $twig->addExtension(new DebugExtension());
        }

        // add runtime loaders
        foreach ($this->config['runtime_loaders'] as $loader) {
            $twig->addRuntimeLoader($this->container->get($loader));
        }

        // render the template
        return $twig->render($template, $data);
    }

    private function getTemplateContents(string $templateIdentifier): string
    {
        // get the directory and file
        $templateFileName = $this->templatesConfig[$templateIdentifier]['filename'];
        $split = \explode('/', $templateFileName);
        $filename = \array_pop($split);
        $dir = \implode('/', $split);

        // create the filesystem adapter
        $filesystem = new Filesystem(new LocalFilesystemAdapter($dir, lazyRootCreation: true));

        // read the file
        return $filesystem->read($filename);
    }

    private function resolveTemplateIncludes(string $templateName): array
    {
        $includes = [];
        foreach ($this->templatesConfig[$templateName]['includes'] ?? [] as $include) {
            if (! isset($this->templatesConfig[$include])) {
                continue;
            }

            if (! isset($this->templatesConfig[$include]['filename'])) {
                continue;
            }

            $includes[] = $include;
            foreach ($this->resolveTemplateIncludes($include) as $subInclude) {
                $includes[] = $subInclude;
            }
        }

        return $includes;
    }
}
