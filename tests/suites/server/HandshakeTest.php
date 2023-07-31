<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

declare(strict_types=1);

namespace WebSocket\Test\Server;

use PHPUnit\Framework\TestCase;
use Phrity\Net\StreamException;
use Phrity\Net\Mock\SocketStream;
use Phrity\Net\Mock\StreamCollection;
use Phrity\Net\Mock\StreamFactory;
use Phrity\Net\Mock\Stack\{
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
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerAccept(schema: 'tcp', port: 8000);
        $this->expectStreamFactoryCreateStreamCollection();
        $this->expectStreamCollection();
        $this->expectStreamCollectionAttach();
        $this->expectStreamCollectionWaitRead()->setReturn(function ($params, $default, $instance) {
            return $instance->getReadable();
        });
        $this->expectStreamCollectionGetReadable();
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
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

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();

        unset($server);
    }

    public function testHandshakeRequestFailure(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerAccept(schema: 'tcp', port: 8000);
        $this->expectStreamFactoryCreateStreamCollection();
        $this->expectStreamCollection();
        $this->expectStreamCollectionAttach();
        $this->expectStreamCollectionWaitRead()->setReturn(function ($params, $default, $instance) {
            return $instance->getReadable();
        });
        $this->expectStreamCollectionGetReadable();
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
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
        $this->expectSocketStreamClose();
        $server->start();

        unset($server);
    }

    public function testHandshakeMethodFailure(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerAccept(schema: 'tcp', port: 8000);
        $this->expectStreamFactoryCreateStreamCollection();
        $this->expectStreamCollection();
        $this->expectStreamCollectionAttach();
        $this->expectStreamCollectionWaitRead()->setReturn(function ($params, $default, $instance) {
            return $instance->getReadable();
        });
        $this->expectStreamCollectionGetReadable();
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
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
            return "POST / HTTP/1.1\r\nHost: localhost\r\n"
            . "Connection: Upgrade\r\nUpgrade: websocket\r\nSec-WebSocket-Key: cktLWXhUdDQ2OXF0ZCFqOQ==\r\n"
            . "Sec-WebSocket-Version: 13\r\n\r\n";
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $server->start();

        unset($server);
    }

    public function testHandshakeConnectionHeaderFailure(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerAccept(schema: 'tcp', port: 8000);
        $this->expectStreamFactoryCreateStreamCollection();
        $this->expectStreamCollection();
        $this->expectStreamCollectionAttach();
        $this->expectStreamCollectionWaitRead()->setReturn(function ($params, $default, $instance) {
            return $instance->getReadable();
        });
        $this->expectStreamCollectionGetReadable();
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
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
            return "GET / HTTP/1.1\r\nHost: localhost\r\n"
            . "Connection: Invalid\r\nUpgrade: websocket\r\nSec-WebSocket-Key: cktLWXhUdDQ2OXF0ZCFqOQ==\r\n"
            . "Sec-WebSocket-Version: 13\r\n\r\n";
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $server->start();

        unset($server);
    }

    public function testHandshakeUpgradeHeaderFailure(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerAccept(schema: 'tcp', port: 8000);
        $this->expectStreamFactoryCreateStreamCollection();
        $this->expectStreamCollection();
        $this->expectStreamCollectionAttach();
        $this->expectStreamCollectionWaitRead()->setReturn(function ($params, $default, $instance) {
            return $instance->getReadable();
        });
        $this->expectStreamCollectionGetReadable();
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
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
            return "GET / HTTP/1.1\r\nHost: localhost\r\n"
            . "Connection: Upgrade\r\nUpgrade: Invalid\r\nSec-WebSocket-Key: cktLWXhUdDQ2OXF0ZCFqOQ==\r\n"
            . "Sec-WebSocket-Version: 13\r\n\r\n";
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $server->start();

        unset($server);
    }

    public function testHandshakeWebSocketKeyHeaderFailure(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerAccept(schema: 'tcp', port: 8000);
        $this->expectStreamFactoryCreateStreamCollection();
        $this->expectStreamCollection();
        $this->expectStreamCollectionAttach();
        $this->expectStreamCollectionWaitRead()->setReturn(function ($params, $default, $instance) {
            return $instance->getReadable();
        });
        $this->expectStreamCollectionGetReadable();
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
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
            return "GET / HTTP/1.1\r\nHost: localhost\r\n"
            . "Connection: Upgrade\r\nUpgrade: websocket\r\n"
            . "Sec-WebSocket-Version: 13\r\n\r\n";
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $server->start();

        unset($server);
    }

    public function testHandshakeWebSocketKeyInvalidFailure(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerAccept(schema: 'tcp', port: 8000);
        $this->expectStreamFactoryCreateStreamCollection();
        $this->expectStreamCollection();
        $this->expectStreamCollectionAttach();
        $this->expectStreamCollectionWaitRead()->setReturn(function ($params, $default, $instance) {
            return $instance->getReadable();
        });
        $this->expectStreamCollectionGetReadable();
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
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
            return "GET / HTTP/1.1\r\nHost: localhost\r\n"
            . "Connection: Upgrade\r\nUpgrade: websocket\r\nSec-WebSocket-Key: jww=\r\n"
            . "Sec-WebSocket-Version: 13\r\n\r\n";
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $server->start();

        unset($server);
    }

    public function testHandshakeResponseFailure(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerAccept(schema: 'tcp', port: 8000);
        $this->expectStreamFactoryCreateStreamCollection();
        $this->expectStreamCollection();
        $this->expectStreamCollectionAttach();
        $this->expectStreamCollectionWaitRead()->setReturn(function ($params, $default, $instance) {
            return $instance->getReadable();
        });
        $this->expectStreamCollectionGetReadable();
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
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
            return "GET / HTTP/1.1\r\nHost: localhost\r\n"
            . "Connection: Upgrade\r\nUpgrade: websocket\r\nSec-WebSocket-Key: cktLWXhUdDQ2OXF0ZCFqOQ==\r\n"
            . "Sec-WebSocket-Version: 13\r\n\r\n";
        });
        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new StreamException(StreamException::FAIL_WRITE);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $server->start();

        unset($server);
    }
}
