<?php

declare(strict_types= 1);

namespace Marshal\Platform;

use Laminas\Diactoros\Response\JsonResponse;
use Marshal\Server\Response\SseResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\StreamInterface;

interface PlatformInterface
{
    public function badRequestResponse(
        ServerRequestInterface $request,
        array $messages = [],
        array $headers = [],
        ?string $template = null,
        array $options = []
    ): ResponseInterface;

    public function errorResponse(
        ServerRequestInterface $request,
        array $messages = [],
        int $status = StatusCodeInterface::STATUS_BAD_REQUEST,
        array $headers = [],
    ): ResponseInterface;

    public function formatResponse(
        ServerRequestInterface $request,
        array $data = [],
        ?string $template = null,
        int $status = StatusCodeInterface::STATUS_OK,
        array $headers = [],
        array $options = []
    ): ResponseInterface;

    public function jsonResponse(
        array $data = [],
        int $status = StatusCodeInterface::STATUS_OK,
        array $headers = [],
        int $encodingOptions = JsonResponse::DEFAULT_JSON_FLAGS
    ): ResponseInterface;

    public function notFoundResponse(
        ServerRequestInterface $request,
        array $messages = [],
        array $headers = [],
        array $options = []
    ): ResponseInterface;

    public function redirectResponse(
        string $uri,
        int $status = StatusCodeInterface::STATUS_FOUND,
        array $headers = []
    ): ResponseInterface;

    public function sseResponse(
        int $status = 200,
        array $headers = [],
        ?StreamInterface $body = null,
        string $protocol = '1.1'
    ): SseResponse;

    public function streamResponse(
        string $template,
        array $signals = [],
        int $status = 200,
        array $headers = []
    ): SseResponse;
}
