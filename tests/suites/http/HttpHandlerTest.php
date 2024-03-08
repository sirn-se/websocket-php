<?php

/**
 * Copyright (C) 2014-2024 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Http;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\SocketStream;
use Phrity\Net\Mock\Stack\ExpectSocketStreamTrait;
use Phrity\Net\StreamFactory;
use Phrity\Net\Uri;
use Psr\Http\Message\{
    RequestInterface,
    UriInterface
};
use RuntimeException;
use Stringable;
use WebSocket\Http\{
    HttpHandler,
    Message,
    Request,
    Response,
    ServerRequest
};

/**
 * Test case for WebSocket\Http\HttpHandler.
 */
class HttpHandlerTest extends TestCase
{
    use ExpectSocketStreamTrait;

    public function setUp(): void
    {
        error_reporting(-1);
        $this->setUpStack();
    }

    public function tearDown(): void
    {
        $this->tearDownStack();
    }

    public function testPushRequest(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $handler = new HttpHandler($stream);
        $this->assertInstanceOf(HttpHandler::class, $handler);

        $request = new Request('GET', 'ws://test.com:123/a/path?a=b');

        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $expect = "GET /a/path?a=b HTTP/1.1\r\nHost: test.com:123\r\n\r\n";
            $this->assertEquals($expect, $params[0]);
        });
        $sent = $handler->push($request);
        $this->assertSame($request, $sent);
        $this->assertInstanceOf(Stringable::class, $handler);
        $this->assertEquals('WebSocket\Http\HttpHandler', "{$handler}");

        fclose($temp);
    }

    public function testPushServerRequest(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $handler = new HttpHandler($stream);
        $this->assertInstanceOf(HttpHandler::class, $handler);

        $request = new ServerRequest('GET', 'ws://test.com:123/a/path?a=b');

        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $expect = "GET /a/path?a=b HTTP/1.1\r\nHost: test.com:123\r\n\r\n";
            $this->assertEquals($expect, $params[0]);
        });
        $sent = $handler->push($request);
        $this->assertSame($request, $sent);

        fclose($temp);
    }

    public function testPullServerRequest(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $handler = new HttpHandler($stream);
        $this->assertInstanceOf(HttpHandler::class, $handler);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET /a/path?a=b HTTP/1.1\r\nA: \r\nA: 0\r\nA: B\r\nHost: test.com:123\r\n\r\n";
        });
        $request = $handler->pull();
        $this->assertInstanceOf(ServerRequest::class, $request);
        $this->assertEquals('/a/path?a=b', $request->getRequestTarget());
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertEquals(['Host' => ['test.com:123'], 'A' => ['0', 'B']], $request->getHeaders());
        $this->assertTrue($request->hasHeader('Host'));
        $uri = $request->getUri();
        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertEquals('test.com', $uri->getHost());
        $this->assertEquals(123, $uri->getPort());
        $this->assertEquals('/a/path', $uri->getPath());
        $this->assertEquals('a=b', $uri->getQuery());

        fclose($temp);
    }

    public function testPushResponse(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $handler = new HttpHandler($stream);
        $this->assertInstanceOf(HttpHandler::class, $handler);

        $response = new Response(200);
        $response = $response->withHeader('Host', 'test.com:123');

        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $expect = "HTTP/1.1 200 OK\r\nHost: test.com:123\r\n\r\n";
            $this->assertEquals($expect, $params[0]);
        });
        $sent = $handler->push($response);
        $this->assertSame($response, $sent);

        fclose($temp);
    }

    public function testPullResponse(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $handler = new HttpHandler($stream);
        $this->assertInstanceOf(HttpHandler::class, $handler);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 200 OK\r\nHost: test.com:123\r\n\r\n";
        });
        $response = $handler->pull();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('1.1', $response->getProtocolVersion());
        $this->assertEquals(['Host' => ['test.com:123']], $response->getHeaders());
        $this->assertTrue($response->hasHeader('Host'));

        fclose($temp);
    }

    public function testPullError(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $handler = new HttpHandler($stream);
        $this->assertInstanceOf(HttpHandler::class, $handler);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "This is not a valid HTTP header\r\n\r\n";
        });
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Invalid Http request.");

        $handler->pull();

        fclose($temp);
    }
}
