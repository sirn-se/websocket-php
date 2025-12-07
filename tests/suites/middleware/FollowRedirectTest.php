<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Middleware;

use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\{
    SocketStream,
    StreamFactory,
};
use Stringable;
use WebSocket\{
    Client,
    Connection,
};
use WebSocket\Exception\{
    HandshakeException,
    ReconnectException,
};
use WebSocket\Middleware\FollowRedirect;
use WebSocket\Test\MockStreamTrait;

/**
 * Test case for WebSocket\Middleware\FollowRedirect
 */
class FollowRedirectTest extends TestCase
{
    use MockStreamTrait;

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
        $client = new Client('ws://localhost:8000/my/mock/path');

        $middleware = new FollowRedirect(2);
        $this->assertEquals('WebSocket\Middleware\FollowRedirect', "{$middleware}");
        $this->assertInstanceOf(Stringable::class, $middleware);

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectWsConnectionCreate();
        $connection = new Connection($client, $stream, false, false);
        $connection->addMiddleware($middleware);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 301 Moved Permanently\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Location: ws://redirect.to/new/target\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $this->expectException(ReconnectException::class);
        $this->expectExceptionMessage('Reconnect requested: ws://redirect.to/new/target');
        $connection->pullHttp();
    }

    public function testMaxRedirect(): void
    {
        $temp = tmpfile();
        $client = new Client('ws://localhost:8000/my/mock/path');

        $middleware = new FollowRedirect(0);

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectWsConnectionCreate();
        $connection = new Connection($client, $stream, false, false);
        $connection->addMiddleware($middleware);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 301 Moved Permanently\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Location: ws://redirect.to/new/target\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $this->expectException(HandshakeException::class);
        $this->expectExceptionMessage('Too many redirect attempts, giving up');
        $connection->pullHttp();
    }

    public function testNoLocation(): void
    {
        $temp = tmpfile();
        $client = new Client('ws://localhost:8000/my/mock/path');

        $middleware = new FollowRedirect(0);

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectWsConnectionCreate();
        $connection = new Connection($client, $stream, false, false);
        $connection->addMiddleware($middleware);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 301 Moved Permanently\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $response = $connection->pullHttp();

        unset($connection);
    }
}
