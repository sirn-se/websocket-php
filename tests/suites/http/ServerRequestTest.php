<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Http;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use Phrity\Net\Uri;
use Psr\Http\Message\{
    ServerRequestInterface,
    UriInterface
};
use WebSocket\Http\ServerRequest;

/**
 * Test case for WebSocket\Http\ServerRequest.
 */
class ServerRequestTest extends TestCase
{
    public function testEmptyRequest(): void
    {
        $request = new ServerRequest();
        $this->assertInstanceOf(ServerRequest::class, $request);
        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertEquals('/', $request->getRequestTarget());
        $this->assertEquals('GET', $request->getMethod());
        $this->assertInstanceOf(UriInterface::class, $request->getUri());
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertEquals([], $request->getHeaders());
        $this->assertFalse($request->hasHeader('none'));
        $this->assertEquals([], $request->getHeader('none'));
        $this->assertEquals('', $request->getHeaderLine('none'));
        $this->assertEquals([], $request->getQueryParams());
    }

    public function testUriInstanceRequest(): void
    {
        $uri = new Uri('ws://test.com:123/a/path?a=b&c=d');
        $request = new ServerRequest('POST', $uri);
        $this->assertEquals('/a/path?a=b&c=d', $request->getRequestTarget());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertInstanceOf(UriInterface::class, $request->getUri());
        $this->assertEquals(['Host' => ['test.com:123']], $request->getHeaders());
        $this->assertTrue($request->hasHeader('Host'));
        $this->assertEquals(['test.com:123'], $request->getHeader('Host'));
        $this->assertEquals('test.com:123', $request->getHeaderLine('Host'));
        $this->assertEquals(['a' => 'b', 'c' => 'd'], $request->getQueryParams());
    }
}
