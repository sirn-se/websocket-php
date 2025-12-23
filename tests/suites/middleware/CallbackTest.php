<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Middleware;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\SocketStream;
use Psr\Log\NullLogger;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface,
};
use Stringable;
use WebSocket\Connection;
use WebSocket\Message\Text;
use WebSocket\Middleware\Callback;
use WebSocket\Test\MockStreamTrait;

/**
 * Test case for WebSocket\Middleware\Callback
 */
class CallbackTest extends TestCase
{
    use MockStreamTrait;

    private Psr17Factory $psrFactory;

    public function setUp(): void
    {
        error_reporting(-1);
        $this->setUpStack();
        $this->psrFactory = new Psr17Factory();
    }

    public function tearDown(): void
    {
        $this->tearDownStack();
    }

    public function testIncoming(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);
        $middleware = new Callback(incoming: function ($stack, $connection) {
            $message = $stack->handleIncoming();
            $message->setContent("Changed message");
            $this->assertEquals('Changed message', $message->getContent());
            return $message;
        });

        $connection->addMiddleware($middleware);
        $this->assertInstanceOf(Stringable::class, $middleware);
        $this->assertEquals('WebSocket\Middleware\Callback', "{$middleware}");

        $this->expectSocketStreamRead()->setReturn(function () {
            return base64_decode('gQw=');
        });
        $this->expectSocketStreamRead()->setReturn(function () {
            return 'Test message';
        });
        $message = $connection->pullMessage();
        $this->assertEquals('Changed message', $message->getContent());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($stream);
    }

    public function testOutgoing(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);

        $connection->addMiddleware(new Callback(outgoing: function ($stack, $connection, $message) {
            $this->assertEquals('Test message', $message->getContent());
            $message->setContent('Changed message');
            $message = $stack->handleOutgoing($message);
            $this->assertEquals('Changed message', $message->getContent());
            return $message;
        }));

        $this->expectSocketStreamWrite();
        $connection->send(new Text('Test message'));

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($stream);
    }

    public function testHttpIncoming(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);

        $connection->addMiddleware(new Callback(httpIncoming: function ($stack, $connection) {
            $message = $stack->handleHttpIncoming();
            $message = $message->withMethod('POST');
            $this->assertEquals('POST', $message->getMethod());
            return $message;
        }));
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET /a/path?a=b HTTP/1.1\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Host: test.com:123\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        /** @var RequestInterface $message */
        $message = $connection->pullHttp();
        $this->assertEquals('POST', $message->getMethod());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($stream);
    }

    public function testHttpOutgoing(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);

        $connection->addMiddleware(new Callback(httpOutgoing: function ($stack, $connection, $message) {
            $message = $stack->handleHttpOutgoing($message);
            $message = $message->withStatus(400);
            $this->assertEquals(400, $message->getStatusCode());
            return $message;
        }));
        $this->expectSocketStreamWrite();
        /** @var ResponseInterface $message */
        $message = $connection->pushHttp($this->psrFactory->createResponse(200));
        $this->assertEquals(400, $message->getStatusCode());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($stream);
    }

    public function testTick(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);

        $connection->addMiddleware(new Callback(tick: function ($stack, $connection) {
            $stack->handleTick();
        }));
        $connection->tick();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($stream);
    }
}
