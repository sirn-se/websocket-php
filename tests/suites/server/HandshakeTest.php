<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Server;

use PHPUnit\Framework\TestCase;
use Phrity\Net\StreamException;
use Phrity\Net\Mock\SocketStream;
use Phrity\Net\Mock\StreamCollection;
use Phrity\Net\Mock\StreamFactory;
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
    use ExpectContextTrait;
    use ExpectSocketServerTrait;
    use ExpectSocketStreamTrait;
    use ExpectStreamCollectionTrait;
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
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server:8000']);
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $this->expectSocketStreamGetRemoteName()->setReturn(function () {
            return "fake-connection";
        });
        $this->expectStreamCollectionAttach();
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) use ($server) {
            $server->stop();
        });
        $this->expectWsServerPerformHandshake();
        $server->start();

        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamClose();
        $this->expectSocketServerClose();
        $server->disconnect();

        unset($server);
    }

    public function testHandshakeRequestVariant(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server:8000']);
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $this->expectSocketStreamGetRemoteName()->setReturn(function () {
            return "fake-connection";
        });
        $this->expectStreamCollectionAttach();
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) use ($server) {
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

        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamClose();
        $this->expectSocketServerClose();
        $server->disconnect();

        unset($server);
    }

    public function testHandshakeRequestFailure(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server:8000']);
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $this->expectSocketStreamGetRemoteName()->setReturn(function () {
            return "fake-connection";
        });
        $this->expectStreamCollectionAttach();
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) use ($server) {
            $server->stop();
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            throw new StreamException(StreamException::FAIL_READ);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamGetMetadata();
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $server->disconnect();

        unset($server);
    }

    public function testHandshakeMethodFailure(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server:8000']);
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $this->expectSocketStreamGetRemoteName()->setReturn(function () {
            return "fake-connection";
        });
        $this->expectStreamCollectionAttach();
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) use ($server) {
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
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $server->disconnect();

        unset($server);
    }

    public function testHandshakeConnectionHeaderFailure(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server:8000']);
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $this->expectSocketStreamGetRemoteName()->setReturn(function () {
            return "fake-connection";
        });
        $this->expectStreamCollectionAttach();
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) use ($server) {
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
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $server->disconnect();

        unset($server);
    }

    public function testHandshakeUpgradeHeaderFailure(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server:8000']);
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $this->expectSocketStreamGetRemoteName()->setReturn(function () {
            return "fake-connection";
        });
        $this->expectStreamCollectionAttach();
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) use ($server) {
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
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $server->disconnect();

        unset($server);
    }

    public function testHandshakeVersionHeaderFailure(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server:8000']);
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $this->expectSocketStreamGetRemoteName()->setReturn(function () {
            return "fake-connection";
        });
        $this->expectStreamCollectionAttach();
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) use ($server) {
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
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $server->disconnect();

        unset($server);
    }

    public function testHandshakeWebSocketKeyHeaderFailure(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server:8000']);
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $this->expectSocketStreamGetRemoteName()->setReturn(function () {
            return "fake-connection";
        });
        $this->expectStreamCollectionAttach();
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) use ($server) {
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
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $server->disconnect();

        unset($server);
    }

    public function testHandshakeWebSocketKeyInvalidFailure(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server:8000']);
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $this->expectSocketStreamGetRemoteName()->setReturn(function () {
            return "fake-connection";
        });
        $this->expectStreamCollectionAttach();
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) use ($server) {
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
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $server->disconnect();

        unset($server);
    }

    public function testHandshakeResponseFailure(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server:8000']);
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $this->expectSocketStreamGetRemoteName()->setReturn(function () {
            return "fake-connection";
        });
        $this->expectStreamCollectionAttach();
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) use ($server) {
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
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $server->disconnect();

        unset($server);
    }
}
