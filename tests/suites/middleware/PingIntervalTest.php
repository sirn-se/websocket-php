<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

declare(strict_types=1);

namespace WebSocket\Test\Middleware;

use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\SocketStream;
use Phrity\Net\Mock\Stack\ExpectSocketStreamTrait;
use WebSocket\Connection;
use WebSocket\Message\Ping;
use WebSocket\Middleware\PingInterval;

/**
 * Test case for WebSocket\Middleware\PingIntervalTest
 */
class PingIntervalTest extends TestCase
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

    public function testPingInterval(): void
    {
        $temp = tmpfile();

        $middleware = new PingInterval(1);
        $this->assertEquals('WebSocket\Middleware\PingInterval', "{$middleware}");

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);
        $connection->addMiddleware($middleware);

        // First tick set interval
        $this->expectSocketStreamIsWritable();
        $connection->tick();

        sleep(1); // Simulate inactivity

        // This tick should now trigger auto-ping
        $this->expectSocketStreamIsWritable();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('iQA='), $params[0]);
        });
        $connection->tick();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($stream);
    }
}
