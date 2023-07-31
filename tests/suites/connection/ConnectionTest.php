<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

declare(strict_types=1);

namespace WebSocket\Test\Connection;

use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\SocketStream;
use Phrity\Net\Mock\Stack\ExpectSocketStreamTrait;
use Psr\Log\NullLogger;
use WebSocket\{
    Connection,
    ConnectionException
};
use WebSocket\Http\{
    Request,
    Response
};
use WebSocket\Message\{
    Ping,
    Text
};
use WebSocket\Middleware\Callback;

/**
 * Test case for WebSocket\Connection: Connection.
 */
class ConnectionTest extends TestCase
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

    public function testCreate(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);
        $this->assertInstanceOf(Connection::class, $connection);

        $this->expectSocketStreamIsConnected();
        $this->assertTrue($connection->isConnected());

        $this->assertEquals('', $connection->getName());
        $this->assertEquals('', $connection->getRemoteName());
        $this->assertEquals('WebSocket\Connection(closed)', "{$connection}");

        $this->expectSocketStreamSetTimeout();
        $this->assertSame($connection, $connection->setTimeout(10));
        $this->assertEquals(10, $connection->getTimeout());

        $this->assertSame($connection, $connection->setLogger(new NullLogger()));
        $this->assertSame($connection, $connection->setFrameSize(64));
        $this->assertEquals(64, $connection->getFrameSize());
        $this->assertSame($connection, $connection->addMiddleware(new Callback()));

        $this->expectSocketStreamIsReadable();
        $this->assertTrue($connection->isReadable());

        $this->expectSocketStreamIsWritable();
        $this->assertTrue($connection->isWritable());

        $this->expectSocketStreamCloseRead();
        $this->expectSocketStreamGetMetadata();
        $this->assertSame($connection, $connection->closeRead());

        $this->expectSocketStreamCloseWrite();
        $this->expectSocketStreamClose();
        $this->assertSame($connection, $connection->closeWrite());

        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $this->assertSame($connection, $connection->disconnect());

        $this->expectSocketStreamIsConnected();
        $this->assertFalse($connection->isConnected());

        unset($stream);
    }

    public function testDestruct(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);

        $this->expectSocketStreamIsConnected();
        $this->assertTrue($connection->isConnected());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();

        unset($stream);
    }

    public function testHttpMessages(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);
        $request = new Request('GET', 'ws://test.com/path');
        $connection->setHandshakeRequest($request);
        $this->assertSame($request, $connection->getHandshakeRequest());

        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals("GET /path HTTP/1.1\r\nHost: test.com\r\n\r\n", $params[0]);
        });
        $connection->pushHttp($request);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 200 OK\r\nHost: test.com\r\n\r\n";
        });
        $response = $connection->pullHttp();
        $this->assertInstanceOf(Response::class, $response);

        $connection->setHandshakeResponse($response);
        $this->assertSame($response, $connection->getHandshakeResponse());

        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $this->assertSame($connection, $connection->disconnect());

        unset($stream);
    }

    public function testWebSocketMessages(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);
        $message = new Text('Test message');

        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('gQxUZXN0IG1lc3NhZ2U'), $params[0]);
        });
        $message = $connection->send($message);
        $this->assertInstanceOf(Text::class, $message);

        $this->expectSocketStreamRead()->setReturn(function () {
            return base64_decode('gQw=');
        });
        $this->expectSocketStreamRead()->setReturn(function () {
            return 'Test message';
        });
        $message = $connection->pullMessage();
        $this->assertInstanceOf(Text::class, $message);

        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $this->assertSame($connection, $connection->disconnect());

        unset($stream);
    }

    public function testSendMessageError(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new ConnectionException('Connection error');
        });
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Connection error');
        $connection->send(new Text('Connection error'));

        unset($stream);
    }

    public function testPullMessageError(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);
        $message = new Text('Test message');

        $this->expectSocketStreamRead()->setReturn(function () {
            throw new ConnectionException('Connection error');
        });
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Connection error');
        $connection->pullMessage();

        unset($stream);
    }
}
