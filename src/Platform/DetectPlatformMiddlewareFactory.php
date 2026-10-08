<?php

declare(strict_types=1);

namespace Marshal\Platform;

use Psr\Container\ContainerInterface;

final class DetectPlatformMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): DetectPlatformMiddleware
    {
        return new DetectPlatformMiddleware($container);
    }
}
