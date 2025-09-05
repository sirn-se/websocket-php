<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Http;

use BadMethodCallException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use PHPUnit\Framework\TestCase;
use Phrity\Logger\Console\ConsoleLogger;
use Phrity\Net\Mock\SocketStream;
use Phrity\Net\Mock\Stack\{
    ExpectContextTrait,
    ExpectSocketStreamTrait,
};
use Phrity\Net\StreamFactory;
use Phrity\Net\Uri;
use Phrity\Util\ErrorHandler;
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
    use ExpectContextTrait;
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
        $this->expectContext();
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
        $this->expectContext();
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
        $this->expectContext();
        $stream = new SocketStream($temp);
        $handler = new HttpHandler($stream);
        $this->assertInstanceOf(HttpHandler::class, $handler);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET /a/path?a=b HTTP/1.1\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "A: \r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "A: 0\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "A: B\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Host: test.com:123\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
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
        $this->expectContext();
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
        $this->expectContext();
        $stream = new SocketStream($temp);
        $handler = new HttpHandler($stream);
        $this->assertInstanceOf(HttpHandler::class, $handler);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 200 OK\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Host: test.com:123\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $response = $handler->pull();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('1.1', $response->getProtocolVersion());
        $this->assertEquals(['Host' => ['test.com:123']], $response->getHeaders());
        $this->assertTrue($response->hasHeader('Host'));

        fclose($temp);
    }

    public function testFragmentedPullResponse(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);
        $handler = new HttpHandler($stream);
        $this->assertInstanceOf(HttpHandler::class, $handler);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 2";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "00 OK\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Host:";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "test.com:123\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\n";
        });
        $response = $handler->pull();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('1.1', $response->getProtocolVersion());
        $this->assertEquals(['Host' => ['test.com:123']], $response->getHeaders());
        $this->assertTrue($response->hasHeader('Host'));

        fclose($temp);
    }

    public function testPullInvalidHttpError(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);
        $handler = new HttpHandler($stream);
        $this->assertInstanceOf(HttpHandler::class, $handler);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "This is not a valid HTTP header\r\n";
        });
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Invalid Http request.");

        $handler->pull();

        fclose($temp);
    }

    public function testPullEmptyReadError(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);
        $handler = new HttpHandler($stream);
        $this->assertInstanceOf(HttpHandler::class, $handler);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return null;
        });
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Could not read Http request.");

        $handler->pull();

        fclose($temp);
    }

    public function testPushUnsupported(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);
        $handler = new HttpHandler($stream);
        $this->assertInstanceOf(HttpHandler::class, $handler);

        $request = new GuzzleRequest('GET', 'ws://test.com:123/a/path?a=b');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Generic MessageInterface currently not supported.");
        $handler->push($request);

        fclose($temp);
    }
}
