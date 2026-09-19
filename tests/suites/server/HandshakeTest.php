<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Server;

use PHPUnit\Framework\TestCase;
use Phrity\Net\StreamException;
use Phrity\Net\Mock\{
    SocketStream,
    StreamCollection,
    StreamFactory,
};
use Phrity\Net\Mock\Stack\{
    ExpectContextTrait,
    ExpectSocketServerTrait,
    ExpectSocketStreamTrait,
    ExpectStreamCollectionTrait,
    ExpectStreamFactoryTrait
};
use WebSocket\{
    ConnectionException,
    Server
};
use WebSocket\Test\{
    MockStreamTrait,
    MockUri
};

/**
 * Test case for WebSocket\Server: Handshake.
 */
class HandshakeTest extends TestCase
{
    use ExpectContextTrait;
    use ExpectSocketServerTrait;
    use ExpectSocketStreamTrait;
    use ExpectStreamCollectionTrait;
    use ExpectStreamFactoryTrait;
    use MockStreamTrait;

    public function setUp(): void
    {
        $this->setUpStack();
    }

    public function tearDown(): void
    {
        $this->tearDownStack();
    }

    public function testHandshakeRequest(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept()->addAssert(function ($method, $params) use ($server) {
            $server->stop();
        });
        $this->expectWsServerPerformHandshake();
        $server->start();

        $this->expectSocketStreamClose();
        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testHandshakeRequestVariant(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept()->addAssert(function ($method, $params) use ($server) {
            $server->stop();
        });

        $this->expectSocketStreamReadLine()->setReturn(function (array $params) {
            return "GET /my/mock/path HTTP/1.1\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function (array $params) {
            return "Host: localhost:8000\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function (array $params) {
            return "Connection: keep-alive, upgrade\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function (array $params) {
            return "Upgrade: websocket\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function (array $params) {
            return "Sec-WebSocket-Key: cktLWXhUdDQ2OXF0ZCFqOQ==\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function (array $params) {
            return "Sec-WebSocket-Version: 13\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function (array $params) {
            return "\r\n";
        });
        $this->expectSocketStreamWrite()->addAssert(function (string $method, array $params): void {
            $expect = "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\n"
            . "Sec-WebSocket-Accept: YmysboNHNoWzWVeQpduY7xELjgU=\r\n\r\n";
            $this->assertEquals($expect, $params[0]);
        })->setReturn(function () {
            return 129;
        });
        $server->start();

        $this->expectSocketStreamClose();
        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testHandshakeRequestFailure(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept()->addAssert(function ($method, $params) use ($server) {
            $server->stop();
        });

        $this->expectSocketStreamReadLine()->setReturn(function () {
            throw new StreamException(StreamException::FAIL_READ);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamGetMetadata();
        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testHandshakeMethodFailure(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept()->addAssert(function ($method, $params) use ($server) {
            $server->stop();
        });

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "POST / HTTP/1.1\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Host: localhost\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Connection: Upgrade\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Upgrade: websocket\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Key: cktLWXhUdDQ2OXF0ZCFqOQ==\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Version: 13\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals("HTTP/1.1 405 Method Not Allowed\r\n\r\n", $params[0]);
        });
        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testHandshakeConnectionHeaderFailure(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept()->addAssert(function ($method, $params) use ($server) {
            $server->stop();
        });

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET / HTTP/1.1\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Host: localhost\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Connection: Invalid\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Upgrade: websocket\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Key: cktLWXhUdDQ2OXF0ZCFqOQ==\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Version: 13\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals("HTTP/1.1 426 Upgrade Required\r\n\r\n", $params[0]);
        });
        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testHandshakeUpgradeHeaderFailure(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept()->addAssert(function ($method, $params) use ($server) {
            $server->stop();
        });

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET / HTTP/1.1\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Host: localhost\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Connection: Upgrade\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Upgrade: Invalid\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Key: cktLWXhUdDQ2OXF0ZCFqOQ==\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Version: 13\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals("HTTP/1.1 426 Upgrade Required\r\n\r\n", $params[0]);
        });
        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testHandshakeVersionHeaderFailure(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept()->addAssert(function ($method, $params) use ($server) {
            $server->stop();
        });

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET / HTTP/1.1\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Host: localhost\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Connection: Upgrade\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Upgrade: websocket\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Key: cktLWXhUdDQ2OXF0ZCFqOQ==\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Version: 12\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals("HTTP/1.1 426 Upgrade Required\r\nSec-WebSocket-Version: 13\r\n\r\n", $params[0]);
        });
        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testHandshakeWebSocketKeyHeaderFailure(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept()->addAssert(function ($method, $params) use ($server) {
            $server->stop();
        });

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET / HTTP/1.1\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Host: localhost\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Connection: Upgrade\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Upgrade: websocket\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Version: 13\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals("HTTP/1.1 426 Upgrade Required\r\n\r\n", $params[0]);
        });
        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testHandshakeWebSocketKeyInvalidFailure(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept()->addAssert(function ($method, $params) use ($server) {
            $server->stop();
        });

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET / HTTP/1.1\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Host: localhost\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Connection: Upgrade\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Upgrade: websocket\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Key: jww=\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Version: 13\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals("HTTP/1.1 426 Upgrade Required\r\n\r\n", $params[0]);
        });
        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testHandshakeResponseFailure(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept()->addAssert(function ($method, $params) use ($server) {
            $server->stop();
        });

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET / HTTP/1.1\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Host: localhost\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Connection: Upgrade\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Upgrade: websocket\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Key: cktLWXhUdDQ2OXF0ZCFqOQ==\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Version: 13\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new StreamException(StreamException::FAIL_WRITE);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamGetMetadata();
        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }
}
