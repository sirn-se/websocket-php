<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

declare(strict_types=1);

namespace WebSocket\Test\Server;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Phrity\Net\StreamException;
use Phrity\Net\Mock\StreamFactory;
use Phrity\Net\Mock\Stack\{
    ExpectSocketServerTrait,
    ExpectSocketStreamTrait,
    ExpectStreamFactoryTrait
};

use Psr\Log\NullLogger;
use WebSocket\{
    ConnectionException,
    Server
};
use WebSocket\Http\ServerRequest;
use WebSocket\Test\{
    MockStreamTrait,
    MockUri
};

/**
 * Test case for WebSocket\Server: Handshake.
 */
class HandshakeTest extends TestCase
{
    use ExpectSocketServerTrait;
    use ExpectSocketStreamTrait;
    use ExpectStreamFactoryTrait;
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

    public function testHandshakeRequest(): void
    {
        // Creating client
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->assertFalse($server->isConnected());
        $this->assertEquals(4096, $server->getFragmentSize());

        $this->expectWsServerAccept();
        $server->accept();

        $this->expectWsServerConnect();
        $this->expectWsServerPerformHandshake();
        $server->connect();

        $request = $server->getHandshakeRequest();
        $this->assertInstanceOf(ServerRequest::class, $request);
        $this->assertEquals('localhost', $request->getUri()->getHost());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($server);
    }

    public function testHandshakeRequestFailure(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerAccept();
        $server->accept();

        $this->expectWsServerConnect();
        $this->expectSocketStreamReadLine()->setReturn(function () {
            throw new StreamException(StreamException::FAIL_READ);
        });
        $this->expectException(ConnectionException::class);
        $this->expectExceptionCode(ConnectionException::SERVER_HANDSHAKE_ERR);
        $this->expectExceptionMessage('Server handshake error');
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $server->connect();

        unset($server);
    }

    public function testHandshakeConnectionHeaderFailure(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerAccept();
        $server->accept();

        $this->expectWsServerConnect();
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET / HTTP/1.1\r\nHost: localhost\r\n"
            . "Connection: Invalid\r\nUpgrade: websocket\r\nSec-WebSocket-Key: cktLWXhUdDQ2OXF0ZCFqOQ==\r\n"
            . "Sec-WebSocket-Version: 13\r\n\r\n";
        });
        $this->expectException(ConnectionException::class);
        $this->expectExceptionCode(ConnectionException::SERVER_HANDSHAKE_ERR);
        $this->expectExceptionMessage("Handshake request with invalid Connection header: 'Invalid'");
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $server->connect();

        unset($server);
    }

    public function testHandshakeUpgradeHeaderFailure(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerAccept();
        $server->accept();

        $this->expectWsServerConnect();
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET / HTTP/1.1\r\nHost: localhost\r\n"
            . "Connection: Upgrade\r\nUpgrade: Invalid\r\nSec-WebSocket-Key: cktLWXhUdDQ2OXF0ZCFqOQ==\r\n"
            . "Sec-WebSocket-Version: 13\r\n\r\n";
        });
        $this->expectException(ConnectionException::class);
        $this->expectExceptionCode(ConnectionException::SERVER_HANDSHAKE_ERR);
        $this->expectExceptionMessage("Handshake request with invalid Upgrade header: 'Invalid'");
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $server->connect();

        unset($server);
    }

    public function testHandshakeWebSocketKeyHeaderFailure(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerAccept();
        $server->accept();

        $this->expectWsServerConnect();
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET / HTTP/1.1\r\nHost: localhost\r\n"
            . "Connection: Upgrade\r\nUpgrade: websocket\r\n"
            . "Sec-WebSocket-Version: 13\r\n\r\n";
        });
        $this->expectException(ConnectionException::class);
        $this->expectExceptionCode(ConnectionException::SERVER_HANDSHAKE_ERR);
        $this->expectExceptionMessage("Handshake request with invalid Sec-WebSocket-Key header: ''");
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $server->connect();

        unset($server);
    }

    public function testHandshakeWebSocketKeyInvalidFailure(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerAccept();
        $server->accept();

        $this->expectWsServerConnect();
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET / HTTP/1.1\r\nHost: localhost\r\n"
            . "Connection: Upgrade\r\nUpgrade: websocket\r\nSec-WebSocket-Key: jww=\r\n"
            . "Sec-WebSocket-Version: 13\r\n\r\n";
        });
        $this->expectException(ConnectionException::class);
        $this->expectExceptionCode(ConnectionException::SERVER_HANDSHAKE_ERR);
        $this->expectExceptionMessage("Handshake request with invalid Sec-WebSocket-Key header: 'jww='");
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $server->connect();

        unset($server);
    }

    public function testHandshakeResponseFailure(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerAccept();
        $server->accept();

        $this->expectWsServerConnect();
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET / HTTP/1.1\r\nHost: localhost\r\n"
            . "Connection: Upgrade\r\nUpgrade: websocket\r\nSec-WebSocket-Key: cktLWXhUdDQ2OXF0ZCFqOQ==\r\n"
            . "Sec-WebSocket-Version: 13\r\n\r\n";
        });
        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new StreamException(StreamException::FAIL_WRITE);
        });
        $this->expectException(ConnectionException::class);
        $this->expectExceptionCode(ConnectionException::SERVER_HANDSHAKE_ERR);
        $this->expectExceptionMessage('Server handshake error');
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $server->connect();

        unset($server);
    }
}
