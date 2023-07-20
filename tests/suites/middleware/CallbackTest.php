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
use Psr\Log\NullLogger;
use WebSocket\Connection;
use WebSocket\Message\Text;
use WebSocket\Middleware\Callback;

/**
 * Test case for WebSocket\Middleware\Callback
 */
class CallbackTest extends TestCase
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

    public function testIncoming(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $connection = new Connection($stream, false, false);

        $connection->addMiddleware(new Callback(incoming: function ($stack, $connection) {
            $message = $stack->handleIncoming();
            $message->setContent("Changed message");
            $this->assertEquals('Changed message', $message->getContent());
            return $message;
        }));

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
        $stream = new SocketStream($temp);

        $connection = new Connection($stream, false, false);

        $connection->addMiddleware(new Callback(outgoing: function ($stack, $connection, $message) {
            $this->assertEquals('Test message', $message->getContent());
            $message->setContent('Changed message');
            $message = $stack->handleOutgoing($message);
            $this->assertEquals('Changed message', $message->getContent());
            return $message;
        }));

        $this->expectSocketStreamWrite();
        $connection->pushMessage(new Text('Test message'));

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($stream);
    }
}
