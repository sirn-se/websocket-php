<?php

/**
 * Copyright (C) 2014-2024 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Middleware;

use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\SocketStream;
use Phrity\Net\Mock\Stack\ExpectSocketStreamTrait;
use Stringable;
use WebSocket\Http\{
    Request,
    Response,
};
use WebSocket\Connection;
use WebSocket\Exception\HandshakeException;
use WebSocket\Middleware\SubprotocolNegotiation;

/**
 * Test case for WebSocket\Middleware\SubprotocolNegotiation
 */
class SubprotocolNegotiationTest extends TestCase
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

    public function testClientProtocolMatch(): void
    {
        $temp = tmpfile();

        $middleware = new SubprotocolNegotiation(['sp-1', 'sp-2', 'sp-3']);
        $this->assertEquals('WebSocket\Middleware\SubprotocolNegotiation', "{$middleware}");
        $this->assertInstanceOf(Stringable::class, $middleware);

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);
        $connection->addMiddleware($middleware);

        $this->expectSocketStreamWrite()->addAssert(
            function (string $method, array $params): void {
                $this->assertEquals(
                    "GET / HTTP/1.1\r\nHost: test.url\r\n"
                    . "Sec-WebSocket-Protocol: sp-1\r\n"
                    . "Sec-WebSocket-Protocol: sp-2\r\n"
                    . "Sec-WebSocket-Protocol: sp-3\r\n\r\n",
                    $params[0]
                );
            }
        );
        $request = new Request('GET', 'ws://test.url');
        $request = $connection->pushHttp($request);
        $this->assertEquals(['sp-1', 'sp-2', 'sp-3'], $request->getHeader('Sec-WebSocket-Protocol'));
        $this->assertNull($connection->getMeta('subprotocolNegotiation.selected'));

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 200 OK\r\nSec-WebSocket-Protocol: sp-2\r\n\r\n";
        });
        $response = $connection->pullHttp();
        $this->assertEquals(['sp-2'], $response->getHeader('Sec-WebSocket-Protocol'));
        $this->assertEquals('sp-2', $connection->getMeta('subprotocolNegotiation.selected'));

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($connection);
    }

    public function testClientProtocolNoMatch(): void
    {
        $temp = tmpfile();

        $middleware = new SubprotocolNegotiation(['sp-1', 'sp-2', 'sp-3']);
        $this->assertEquals('WebSocket\Middleware\SubprotocolNegotiation', "{$middleware}");
        $this->assertInstanceOf(Stringable::class, $middleware);

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);
        $connection->addMiddleware($middleware);

        $this->expectSocketStreamWrite()->addAssert(
            function (string $method, array $params): void {
                $this->assertEquals(
                    "GET / HTTP/1.1\r\nHost: test.url\r\n"
                    . "Sec-WebSocket-Protocol: sp-1\r\n"
                    . "Sec-WebSocket-Protocol: sp-2\r\n"
                    . "Sec-WebSocket-Protocol: sp-3\r\n\r\n",
                    $params[0]
                );
            }
        );
        $request = new Request('GET', 'ws://test.url');
        $request = $connection->pushHttp($request);
        $this->assertEquals(['sp-1', 'sp-2', 'sp-3'], $request->getHeader('Sec-WebSocket-Protocol'));
        $this->assertNull($connection->getMeta('subprotocolNegotiation.selected'));

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 200 OK\r\n\r\n";
        });
        $response = $connection->pullHttp();
        $this->assertEquals([], $response->getHeader('Sec-WebSocket-Protocol'));
        $this->assertNull($connection->getMeta('subprotocolNegotiation.selected'));

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($connection);
    }

    public function testClientProtocolRequire(): void
    {
        $temp = tmpfile();

        $middleware = new SubprotocolNegotiation(['sp-1', 'sp-2', 'sp-3'], true);
        $this->assertEquals('WebSocket\Middleware\SubprotocolNegotiation', "{$middleware}");
        $this->assertInstanceOf(Stringable::class, $middleware);

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);
        $connection->addMiddleware($middleware);

        $this->expectSocketStreamWrite()->addAssert(
            function (string $method, array $params): void {
                $this->assertEquals(
                    "GET / HTTP/1.1\r\nHost: test.url\r\n"
                    . "Sec-WebSocket-Protocol: sp-1\r\n"
                    . "Sec-WebSocket-Protocol: sp-2\r\n"
                    . "Sec-WebSocket-Protocol: sp-3\r\n\r\n",
                    $params[0]
                );
            }
        );
        $request = new Request('GET', 'ws://test.url');
        $request = $connection->pushHttp($request);
        $this->assertEquals(['sp-1', 'sp-2', 'sp-3'], $request->getHeader('Sec-WebSocket-Protocol'));
        $this->assertNull($connection->getMeta('subprotocolNegotiation.selected'));

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 200 OK\r\n\r\n";
        });
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $this->expectException(HandshakeException::class);
        $this->expectExceptionMessage('Could not resolve subprotocol.');
        $connection->pullHttp();
    }

    public function testServerProtocolMatch(): void
    {
        $temp = tmpfile();

        $middleware = new SubprotocolNegotiation(['sp-1', 'sp-2', 'sp-3']);
        $this->assertEquals('WebSocket\Middleware\SubprotocolNegotiation', "{$middleware}");
        $this->assertInstanceOf(Stringable::class, $middleware);

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);
        $connection->addMiddleware($middleware);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET / HTTP/1.1\r\nHost: test.url\r\n"
                . "Sec-WebSocket-Protocol: sp-11\r\n"
                . "Sec-WebSocket-Protocol: sp-2\r\n"
                . "Sec-WebSocket-Protocol: sp-33\r\n\r\n";
        });
        $request = $connection->pullHttp();
        $this->assertEquals(['sp-11', 'sp-2', 'sp-33'], $request->getHeader('Sec-WebSocket-Protocol'));
        $this->assertEquals('sp-2', $connection->getMeta('subprotocolNegotiation.selected'));

        $response = new Response(200);
        $this->expectSocketStreamWrite()->addAssert(
            function (string $method, array $params): void {
                $this->assertEquals(
                    "HTTP/1.1 200 OK\r\nSec-WebSocket-Protocol: sp-2\r\n\r\n",
                    $params[0]
                );
            }
        );
        $response = $connection->pushHttp($response);
        $this->assertEquals(['sp-2'], $response->getHeader('Sec-WebSocket-Protocol'));
        $this->assertEquals('sp-2', $connection->getMeta('subprotocolNegotiation.selected'));

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($connection);
    }

    public function testServerProtocolNoMatch(): void
    {
        $temp = tmpfile();

        $middleware = new SubprotocolNegotiation(['sp-1', 'sp-2', 'sp-3']);
        $this->assertEquals('WebSocket\Middleware\SubprotocolNegotiation', "{$middleware}");
        $this->assertInstanceOf(Stringable::class, $middleware);

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);
        $connection->addMiddleware($middleware);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET / HTTP/1.1\r\nHost: test.url\r\n"
                . "Sec-WebSocket-Protocol: sp-11\r\n"
                . "Sec-WebSocket-Protocol: sp-22\r\n"
                . "Sec-WebSocket-Protocol: sp-33\r\n\r\n";
        });
        $request = $connection->pullHttp();
        $this->assertEquals(['sp-11', 'sp-22', 'sp-33'], $request->getHeader('Sec-WebSocket-Protocol'));
        $this->assertNull($connection->getMeta('subprotocolNegotiation.selected'));

        $response = new Response(200);
        $this->expectSocketStreamWrite()->addAssert(
            function (string $method, array $params): void {
                $this->assertEquals(
                    "HTTP/1.1 200 OK\r\n\r\n",
                    $params[0]
                );
            }
        );
        $response = $connection->pushHttp($response);
        $this->assertEquals([], $response->getHeader('Sec-WebSocket-Protocol'));
        $this->assertNull($connection->getMeta('subprotocolNegotiation.selected'));

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($connection);
    }

    public function testServerProtocolRequire(): void
    {
        $temp = tmpfile();

        $middleware = new SubprotocolNegotiation(['sp-1', 'sp-2', 'sp-3'], true);
        $this->assertEquals('WebSocket\Middleware\SubprotocolNegotiation', "{$middleware}");
        $this->assertInstanceOf(Stringable::class, $middleware);

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);
        $connection->addMiddleware($middleware);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET / HTTP/1.1\r\nHost: test.url\r\n"
                . "Sec-WebSocket-Protocol: sp-11\r\n"
                . "Sec-WebSocket-Protocol: sp-22\r\n"
                . "Sec-WebSocket-Protocol: sp-33\r\n\r\n";
        });
        $request = $connection->pullHttp();
        $this->assertEquals(['sp-11', 'sp-22', 'sp-33'], $request->getHeader('Sec-WebSocket-Protocol'));
        $this->assertNull($connection->getMeta('subprotocolNegotiation.selected'));

        $response = new Response(200);
        $this->expectSocketStreamWrite()->addAssert(
            function (string $method, array $params): void {
                $this->assertEquals(
                    "HTTP/1.1 426 Upgrade Required\r\n\r\n",
                    $params[0]
                );
            }
        );
        $response = $connection->pushHttp($response);
        $this->assertEquals([], $response->getHeader('Sec-WebSocket-Protocol'));
        $this->assertNull($connection->getMeta('subprotocolNegotiation.selected'));

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($connection);
    }
}
