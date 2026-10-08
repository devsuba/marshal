<?php

declare(strict_types=1);

namespace Marshal\Database\Handler;

use Psr\Container\ContainerInterface;

final class ContentHandlerFactory
{
    public function __invoke(ContainerInterface $container): ContentHandler
    {
        $databaseConfig = $container->get('config')['database'] ?? [];
        $schemaConfig = $container->get('config')['schema'] ?? [];
        return new ContentHandler($container, $databaseConfig, $schemaConfig);
    }
}
