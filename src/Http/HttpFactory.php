<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Http;

use Phrity\Net\UriFactory;
use Psr\Http\Message\{
    RequestFactoryInterface,
    RequestInterface,
    ResponseFactoryInterface,
    ResponseInterface,
    ServerRequestFactoryInterface,
    ServerRequestInterface,
    UriFactoryInterface,
    UriInterface,
};

/**
 * WebSocket\Http\HttpFactory
 * Only used for handshake procedure.
 */
class HttpFactory extends UriFactory implements
    RequestFactoryInterface,
    ResponseFactoryInterface,
    ServerRequestFactoryInterface
{
    public function __construct()
    {
    }

    /**
     * Create a new request.
     * @param string $method
     * @param UriInterface|string $uri
     */
    public function createRequest(string $method, mixed $uri): RequestInterface
    {
        return new Request($method, $uri);
    }

    /**
     * @param int $code
     * @param string $reasonPhrase
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response($code, $reasonPhrase);
    }

    /**
     * @param string $method
     * @param UriInterface|string $uri
     * @param array<string, mixed> $serverParams
     */
    public function createServerRequest(string $method, mixed $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($method, $uri);
    }
}
