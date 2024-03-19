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
use WebSocket\Message\Close;
use WebSocket\Middleware\CloseHandler;

/**
 * Test case for WebSocket\Middleware\CloseHandler
 */
class CloseHandlerTest extends TestCase
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

    public function testLocalClose(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);

        $middleware = new CloseHandler();
        $connection->addMiddleware($middleware);
        $this->assertInstanceOf(Stringable::class, $middleware);
        $this->assertEquals('WebSocket\Middleware\CloseHandler', "{$middleware}");

        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('iAYD6HR0Zm4'), $params[0]);
        });
        $this->expectSocketStreamIsReadable();
        $this->expectSocketStreamCloseWrite();
        $this->expectSocketStreamGetMetadata();
        $connection->send(new Close(1000, 'ttfn'));

        $this->expectSocketStreamRead()->setReturn(function () {
            return base64_decode('iAY==');
        });
        $this->expectSocketStreamRead()->setReturn(function () {
            return base64_decode('A+h0dGZu');
        });
        $this->expectSocketStreamIsWritable();
        $this->expectSocketStreamClose();
        $message = $connection->pullMessage();
        $this->assertInstanceOf(Close::class, $message);

        unset($stream);
    }

    public function testRemoteClose(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);
        $connection->addMiddleware(new CloseHandler());

        $this->expectSocketStreamRead()->setReturn(function () {
            return base64_decode('iAY==');
        });
        $this->expectSocketStreamRead()->setReturn(function () {
            return base64_decode('A+h0dGZu');
        });
        $this->expectSocketStreamIsWritable();
        $this->expectSocketStreamCloseRead();
        $this->expectSocketStreamGetMetadata();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('iBoD6ENsb3NlIGFja25vd2xlZGdlZDogMTAwMA=='), $params[0]);
        });
        $this->expectSocketStreamIsReadable();
        $this->expectSocketStreamClose();
        $message = $connection->pullMessage();
        $this->assertInstanceOf(Close::class, $message);

        unset($stream);
    }
}
