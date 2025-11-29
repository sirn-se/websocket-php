<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Connection;

use PHPUnit\Framework\TestCase;
use Phrity\Net\Context;
use Phrity\Net\Mock\SocketStream;
use Phrity\Net\Mock\Stack\{
    ExpectContextTrait,
    ExpectSocketStreamTrait,
};
use Psr\Log\NullLogger;
use Stringable;
use WebSocket\{
    Client,
    Connection,
};
use WebSocket\Exception\{
    BadOpcodeException,
    BadUriException,
    ConnectionClosedException,
    ConnectionFailureException,
    ConnectionTimeoutException
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
use WebSocket\Test\MockStreamTrait;

/**
 * Test case for WebSocket\Connection: Connection.
 */
class ConnectionTest extends TestCase
{
    use ExpectContextTrait;
    use ExpectSocketStreamTrait;
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

    public function testCreate(): void
    {
        $temp = tmpfile();
        $client = new Client('ws://localhost:8000/my/mock/path');

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectWsConnectionCreate();
        $connection = new Connection($client, $stream, false, false);
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertInstanceOf(Stringable::class, $connection);
        $this->assertInstanceOf(Client::class, $connection->getHandler());

        $this->expectSocketStreamIsConnected();
        $this->assertTrue($connection->isConnected());

        $this->assertEquals('<unknown>', $connection->getName());
        $this->assertEquals('<unknown>', $connection->getRemoteName());
        $this->assertEquals('WebSocket\Connection(<unknown>:<unknown>)', "{$connection}");
        $connection->tick();
        $connection->setMeta('test.meta.1', 'meta.data.1');
        $connection->setMeta('test.meta.2', 'meta.data.2');
        $this->assertEquals('meta.data.1', $connection->getMeta('test.meta.1'));
        $this->assertEquals('meta.data.2', $connection->getMeta('test.meta.2'));

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

        $this->assertFalse($connection->isConnected());

        $this->expectSocketStreamGetContext();
        $this->assertInstanceOf(Context::class, $connection->getContext());

        unset($connection);
    }

    public function testHttpMessages(): void
    {
        $temp = tmpfile();
        $client = new Client('ws://localhost:8000/my/mock/path');

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectWsConnectionCreate();
        $connection = new Connection($client, $stream, false, false);
        $request = new Request('GET', 'ws://test.com/path');
        $connection->setHandshakeRequest($request);
        $this->assertSame($request, $connection->getHandshakeRequest());

        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals("GET /path HTTP/1.1\r\nHost: test.com\r\n\r\n", $params[0]);
        });
        $connection->pushHttp($request);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 200 OK\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Host: test.com\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $response = $connection->pullHttp();
        $this->assertInstanceOf(Response::class, $response);

        $connection->setHandshakeResponse($response);
        $this->assertSame($response, $connection->getHandshakeResponse());

        $this->expectSocketStreamClose();
        $this->assertSame($connection, $connection->disconnect());

        unset($connection);
    }

    public function testWebSocketMessages(): void
    {
        $temp = tmpfile();
        $client = new Client('ws://localhost:8000/my/mock/path');

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectWsConnectionCreate();
        $connection = new Connection($client, $stream, false, false);
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
        $this->assertSame($connection, $connection->disconnect());

        unset($connection);
    }

    public function testSendHttpError(): void
    {
        $temp = tmpfile();
        $client = new Client('ws://localhost:8000/my/mock/path');

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectWsConnectionCreate();
        $connection = new Connection($client, $stream, false, false);

        $this->expectSocketStreamWrite()->setReturn(function () use ($connection) {
            throw new ConnectionClosedException($connection);
        });
        $this->expectException(ConnectionClosedException::class);
        $this->expectExceptionMessage('Connection has unexpectedly closed');
        $connection->pushHttp(new Request());

        unset($connection);
    }

    public function testPullHttpError(): void
    {
        $temp = tmpfile();
        $client = new Client('ws://localhost:8000/my/mock/path');

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectWsConnectionCreate();
        $connection = new Connection($client, $stream, false, false);

        $this->expectSocketStreamReadLine()->setReturn(function () use ($connection) {
            throw new ConnectionClosedException($connection);
        });
        $this->expectException(ConnectionClosedException::class);
        $this->expectExceptionMessage('Connection has unexpectedly closed');
        $connection->pullHttp();

        unset($connection);
    }

    public function testSendMessageError(): void
    {
        $temp = tmpfile();
        $client = new Client('ws://localhost:8000/my/mock/path');

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectWsConnectionCreate();
        $connection = new Connection($client, $stream, false, false);

        $this->expectSocketStreamWrite()->setReturn(function () use ($connection) {
            throw new ConnectionClosedException($connection);
        });
        $this->expectException(ConnectionClosedException::class);
        $this->expectExceptionMessage('Connection has unexpectedly closed');
        $connection->send(new Text('Connection error'));

        unset($connection);
    }

    public function testPullMessageError(): void
    {
        $temp = tmpfile();
        $client = new Client('ws://localhost:8000/my/mock/path');

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectWsConnectionCreate();
        $connection = new Connection($client, $stream, false, false);

        $this->expectSocketStreamRead()->setReturn(function () use ($connection) {
            throw new ConnectionClosedException($connection);
        });
        $this->expectException(ConnectionClosedException::class);
        $this->expectExceptionMessage('Connection has unexpectedly closed');
        $connection->pullMessage();

        unset($connection);
    }
}
