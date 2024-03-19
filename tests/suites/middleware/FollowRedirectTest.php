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
use WebSocket\Connection;
use WebSocket\Exception\{
    HandshakeException,
    ReconnectException,
};
use WebSocket\Middleware\FollowRedirect;

/**
 * Test case for WebSocket\Middleware\FollowRedirect
 */
class FollowRedirectTest extends TestCase
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

    public function testRedirect(): void
    {
        $temp = tmpfile();

        $middleware = new FollowRedirect(2);
        $this->assertEquals('WebSocket\Middleware\FollowRedirect', "{$middleware}");
        $this->assertInstanceOf(Stringable::class, $middleware);

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);
        $connection->addMiddleware($middleware);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 301 Moved Permanently\r\nLocation: ws://redirect.to/new/target\r\n\r\n";
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $this->expectException(ReconnectException::class);
        $this->expectExceptionMessage('Reconnect requested: ws://redirect.to/new/target');
        $connection->pullHttp();
    }

    public function testMaxRedirect(): void
    {
        $temp = tmpfile();

        $middleware = new FollowRedirect(0);

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);
        $connection->addMiddleware($middleware);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 301 Moved Permanently\r\nLocation: ws://redirect.to/new/target\r\n\r\n";
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $this->expectException(HandshakeException::class);
        $this->expectExceptionMessage('0 of 0 redirect attempts, giving up');
        $connection->pullHttp();
    }

    public function testNoLocation(): void
    {
        $temp = tmpfile();

        $middleware = new FollowRedirect(0);

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);
        $connection->addMiddleware($middleware);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 301 Moved Permanently\r\n\r\n";
        });
        $response = $connection->pullHttp();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($connection);
    }
}
