<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Middleware;

use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\SocketStream;
use Stringable;
use WebSocket\Connection;
use WebSocket\Message\Ping;
use WebSocket\Middleware\PingResponder;
use WebSocket\Test\MockStreamTrait;

/**
 * Test case for WebSocket\Middleware\PingResponder
 */
class PingResponderTest extends TestCase
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

    public function testPingAutoResponse(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectWsConnectionCreate();
        $connection = new Connection($stream, false, false);

        $middleware = new PingResponder();
        $connection->addMiddleware($middleware);
        $this->assertInstanceOf(Stringable::class, $middleware);
        $this->assertEquals('WebSocket\Middleware\PingResponder', "{$middleware}");

        $message = new Ping();

        $this->expectSocketStreamRead()->setReturn(function () {
            return base64_decode('iQA=');
        });
        $this->expectSocketStreamIsWritable();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('igA='), $params[0]);
        });
        $message = $connection->pullMessage();
        $this->assertInstanceOf(Ping::class, $message);

        $this->expectSocketStreamClose();
        $this->assertSame($connection, $connection->disconnect());

        unset($stream);
    }
}
