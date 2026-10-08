<?php

declare(strict_types=1);

namespace Marshal\Platform;

use Marshal\Platform\API\APIPlatform;
use Marshal\Platform\Web\WebPlatform;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DetectPlatformMiddleware implements MiddlewareInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @inheritDoc
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Server\RequestHandlerInterface $handler
     * @throws \InvalidArgumentException
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $platform = $this->isApiRequest($request)
            ? $this->container->get(APIPlatform::class)
            : $this->container->get(WebPlatform::class);

        if (! $platform instanceof PlatformInterface) {
            throw new \InvalidArgumentException(\sprintf(
                "Expected an instance of %s, given %s instead",
                PlatformInterface::class,
                \get_debug_type($platform)
            ));
        }

        return $handler->handle($request->withAttribute(PlatformInterface::class, $platform));
    }

    private function isApiRequest(ServerRequestInterface $request): bool
    {
        return false !== \mb_strpos($request->getUri()->getPath(), '/api/');
    }
}
