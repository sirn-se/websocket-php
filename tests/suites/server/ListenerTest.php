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
use Phrity\Net\Mock\StreamCollection;
use Phrity\Net\Mock\StreamFactory;
use Phrity\Net\Mock\Stack\{
    ExpectSocketServerTrait,
    ExpectSocketStreamTrait,
    ExpectStreamCollectionTrait,
    ExpectStreamFactoryTrait
};
use Psr\Log\NullLogger;
use WebSocket\{
    BadOpcodeException,
    Connection,
    Server
};
use WebSocket\Http\{
    ServerRequest
};
use WebSocket\Message\{
    Binary,
    Close,
    Ping,
    Pong,
    Text
};
use WebSocket\Test\{
    MockStreamTrait,
    MockUri
};

/**
 * Test case for WebSocket\Server: Listener operation.
 */
class ListenerTest extends TestCase
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

    public function testListeners(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $server->onConnect(function ($server, $connection, $request) {
            $this->assertInstanceOf(Server::class, $server);
            $this->assertInstanceOf(Connection::class, $connection);
            $this->assertInstanceOf(ServerRequest::class, $request);
            $server->stop();
        });
        $server->onText(function ($server, $connection, $message) {
            $this->assertInstanceOf(Server::class, $server);
            $this->assertInstanceOf(Connection::class, $connection);
            $this->assertInstanceOf(Text::class, $message);
            $server->stop();
        });
        $server->onBinary(function ($server, $connection, $message) {
            $this->assertInstanceOf(Server::class, $server);
            $this->assertInstanceOf(Connection::class, $connection);
            $this->assertInstanceOf(Binary::class, $message);
            $server->stop();
        });
        $server->onPing(function ($server, $connection, $message) {
            $this->assertInstanceOf(Server::class, $server);
            $this->assertInstanceOf(Connection::class, $connection);
            $this->assertInstanceOf(Ping::class, $message);
            $server->stop();
        });
        $server->onPong(function ($server, $connection, $message) {
            $this->assertInstanceOf(Server::class, $server);
            $this->assertInstanceOf(Connection::class, $connection);
            $this->assertInstanceOf(Pong::class, $message);
            $server->stop();
        });
        $server->onClose(function ($server, $connection, $message) {
            $this->assertInstanceOf(Server::class, $server);
            $this->assertInstanceOf(Connection::class, $connection);
            $this->assertInstanceOf(Close::class, $message);
            $server->stop();
        });
        $server->onDisconnect(function ($server, $connection) {
            $this->assertInstanceOf(Server::class, $server);
            $this->assertInstanceOf(Connection::class, $connection);
            $server->stop();
        });
        $server->onError(function ($server, $connection, $exception) {
            $this->assertInstanceOf(Server::class, $server);
            $this->assertInstanceOf(BadOpcodeException::class, $exception);
            $server->stop();
        });
        $server->onTick(function ($server) {
            $this->assertInstanceOf(Server::class, $server);
        });

        $this->expectWsServerSetup(schema: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['@server']);
        // Accept connection
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectSocketStreamGetRemoteName()->setReturn(function () {
            return 'fake-connection-1';
        });
        $this->expectStreamCollectionAttach();
        $this->expectSocketStreamGetLocalName()->setReturn(function () {
            return 'fake-connection-1';
        });
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $this->expectWsServerPerformHandshake();
        $server->start();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['fake-connection-1']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('gQA=');
        });
        $server->start();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['fake-connection-1']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('ggA=');
        });
        $server->start();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['fake-connection-1']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('iQA=');
        });
        $server->start();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['fake-connection-1']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('igA=');
        });
        $server->start();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['fake-connection-1']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('iAA=');
        });
        $server->start();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['fake-connection-1']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('gwA=');
        });
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectSocketStreamClose();
        $this->expectSocketServerClose();
        $this->expectSocketStreamIsConnected();

        $server->disconnect();

        unset($server);
    }

    public function xxxtestBroadcast(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());
$server->setLogger(new \WebSocket\Test\EchoLog());

        $this->expectWsServerSetup(schema: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['@server']);
        // Accept first connection
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectSocketStreamGetRemoteName()->setReturn(function () {
            return 'fake-connection-1';
        });
        $this->expectStreamCollectionAttach();
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) use ($server) {
            $server->stop();
        });
        $this->expectWsServerPerformHandshake();
        $server->start();

        $this->assertEquals(1, $server->getConnectionCount());

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['@server', 'fake-connection-1']);
        // Accept second connection
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectSocketStreamGetRemoteName()->setReturn(function () {
            return "fake-connection-2";
        });
        $this->expectStreamCollectionAttach();
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) use ($server) {
            $server->stop();
        });
        $this->expectWsServerPerformHandshake();

        // Read from first connection
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('gZM=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('IW+Vrg==');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(19, $params[0]);
        })->setReturn(function () {
            return base64_decode('cwr2y0gZ/MBGT/SOTArm3UAI8A==');
        });
        $server->start();

        $this->assertEquals(2, $server->getConnectionCount());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();

        unset($server);
    }
}
