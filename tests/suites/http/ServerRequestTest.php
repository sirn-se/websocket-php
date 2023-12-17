<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
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
use Stringable;
use WebSocket\Http\{
    Message,
    ServerRequest
};

/**
 * Test case for WebSocket\Http\ServerRequest.
 */
class ServerRequestTest extends TestCase
{
    public function setUp(): void
    {
        error_reporting(-1);
    }

    public function testEmptyRequest(): void
    {
        $request = new ServerRequest();
        $this->assertInstanceOf(ServerRequest::class, $request);
        $this->assertInstanceOf(Message::class, $request);
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
        $this->assertInstanceOf(Stringable::class, $request);
        $this->assertEquals('WebSocket\Http\ServerRequest(GET /)', "{$request}");
        $this->assertEquals([
            'GET / HTTP/1.1',
        ], $request->getAsArray());
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
        $this->assertEquals('WebSocket\Http\ServerRequest(POST /a/path?a=b&c=d)', "{$request}");
        $this->assertEquals([
            'POST /a/path?a=b&c=d HTTP/1.1',
            'Host: test.com:123',
        ], $request->getAsArray());
    }

    public function testGetServerParamsError(): void
    {
        $request = new ServerRequest();
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Not implemented.');
        $request->getServerParams();
    }

    public function testGetCookieParamsError(): void
    {
        $request = new ServerRequest();
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Not implemented.');
        $request->getCookieParams();
    }

    public function testWithCookieParamsError(): void
    {
        $request = new ServerRequest();
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Not implemented.');
        $request->withCookieParams([]);
    }

    public function testWithQueryParamsError(): void
    {
        $request = new ServerRequest();
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Not implemented.');
        $request->withQueryParams([]);
    }

    public function testGetUploadedFilesError(): void
    {
        $request = new ServerRequest();
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Not implemented.');
        $request->getUploadedFiles();
    }

    public function testWithUploadedFilesError(): void
    {
        $request = new ServerRequest();
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Not implemented.');
        $request->withUploadedFiles([]);
    }

    public function testGetParsedBodyError(): void
    {
        $request = new ServerRequest();
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Not implemented.');
        $request->getParsedBody([]);
    }

    public function testWithParsedBodyError(): void
    {
        $request = new ServerRequest();
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Not implemented.');
        $request->withParsedBody(null);
    }

    public function testGetAttributesError(): void
    {
        $request = new ServerRequest();
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Not implemented.');
        $request->getAttributes();
    }

    public function testGetAttributeError(): void
    {
        $request = new ServerRequest();
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Not implemented.');
        $request->getAttribute('name');
    }

    public function testWithAttributeError(): void
    {
        $request = new ServerRequest();
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Not implemented.');
        $request->withAttribute('name', 'value');
    }

    public function testWithoutAttributeError(): void
    {
        $request = new ServerRequest();
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Not implemented.');
        $request->withoutAttribute('name');
    }
}
