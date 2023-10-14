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
use WebSocket\Middleware\{
    Callback,
    CloseHandler,
    PingResponder
};

/**
 * Test case for WebSocket\Middleware\ stack processing
 */
class ProcessStackTest extends TestCase
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

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);

        $connection->addMiddleware(new Callback(incoming: function ($stack, $connection) {
            $message = $stack->handleIncoming();
            $message->setContent($message->getContent() . "<-A");
            $this->assertEquals('Test message<-C<-B<-A', $message->getContent());
            return $message;
        }));
        $connection->addMiddleware(new Callback());
        $connection->addMiddleware(new CloseHandler());
        $connection->addMiddleware(new PingResponder());
        $connection->addMiddleware(new Callback(incoming: function ($stack, $connection) {
            $message = $stack->handleIncoming();
            $message->setContent($message->getContent() . "<-B");
            $this->assertEquals('Test message<-C<-B', $message->getContent());
            return $message;
        }));
        $connection->addMiddleware(new Callback(incoming: function ($stack, $connection) {
            $message = $stack->handleIncoming();
            $message->setContent($message->getContent() . "<-C");
            $this->assertEquals('Test message<-C', $message->getContent());
            return $message;
        }));
        $connection->setLogger(new NullLogger());

        $this->expectSocketStreamRead()->setReturn(function () {
            return base64_decode('gQw=');
        });
        $this->expectSocketStreamRead()->setReturn(function () {
            return 'Test message';
        });
        $message = $connection->pullMessage();
        $this->assertEquals('Test message<-C<-B<-A', $message->getContent());

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

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);

        $connection->addMiddleware(new Callback(outgoing: function ($stack, $connection, $message) {
            $this->assertEquals('Test message', $message->getContent());
            $message->setContent($message->getContent() . "->A");
            $message = $stack->handleOutgoing($message);
            $message->setContent($message->getContent() . "<-A");
            $this->assertEquals('Test message->A->B->C<-C<-B<-A', $message->getContent());
            return $message;
        }));
        $connection->addMiddleware(new Callback());
        $connection->addMiddleware(new CloseHandler());
        $connection->addMiddleware(new PingResponder());
        $connection->addMiddleware(new Callback(outgoing: function ($stack, $connection, $message) {
            $this->assertEquals('Test message->A', $message->getContent());
            $message->setContent($message->getContent() . "->B");
            $message = $stack->handleOutgoing($message);
            $message->setContent($message->getContent() . "<-B");
            $this->assertEquals('Test message->A->B->C<-C<-B', $message->getContent());
            return $message;
        }));
        $connection->addMiddleware(new Callback(outgoing: function ($stack, $connection, $message) {
            $this->assertEquals('Test message->A->B', $message->getContent());
            $message->setContent($message->getContent() . "->C");
            $message = $stack->handleOutgoing($message);
            $message->setContent($message->getContent() . "<-C");
            $this->assertEquals('Test message->A->B->C<-C', $message->getContent());
            return $message;
        }));
        $connection->setLogger(new NullLogger());

        $this->expectSocketStreamWrite();
        $connection->send(new Text('Test message'));

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($stream);
    }
}
