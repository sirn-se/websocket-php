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
use WebSocket\Middleware\PingResponder;

/**
 * Test case for WebSocket\Middleware\PingResponder
 */
class PingResponderTest extends TestCase
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

    public function testPingAutoResponse(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $connection = new Connection($stream, false, false);
        $connection->addMiddleware(new PingResponder());
        $message = new Ping();

        $this->expectSocketStreamRead()->setReturn(function () {
            return base64_decode('iQA=');
        });
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('igA='), $params[0]);
        });
        $message = $connection->pullMessage();
        $this->assertInstanceOf(Ping::class, $message);

        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $this->assertSame($connection, $connection->disconnect());

        unset($stream);
    }
}
