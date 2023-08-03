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
use Phrity\Net\StreamException;
use Psr\Log\NullLogger;
use WebSocket\{
    Connection,
    Server
};
use WebSocket\Exception\{
    BadOpcodeException,
    ConnectionClosedException,
    ServerException
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
use WebSocket\Middleware\Callback;
use WebSocket\Test\{
    MockStreamTrait,
    MockUri
};

/**
 * Test case for WebSocket\Server: Core operation.
 */
class ServerTest extends TestCase
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

    public function xxxtestListeners(): void
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

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
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
        $server->start();

        $this->expectSocketStreamClose();
        $this->expectSocketServerClose();
        $this->expectSocketStreamIsConnected();

        $server->disconnect();

        unset($server);
    }

    public function xxxtestMiddlewares(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $server->addMiddleware(new Callback());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) use ($server) {
            $server->stop();
        });
        $this->expectWsServerPerformHandshake();
        $server->start();

        $server->addMiddleware(new Callback());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();

        unset($server);
    }

    public function xxxtestBroadcastSend(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) use ($server) {
            $server->stop();
        });
        $this->expectWsServerPerformHandshake();
        $server->start();

        $this->expectSocketStreamIsWritable();
        $this->expectSocketStreamWrite();
        $message = $server->text('Test message');
        $this->assertInstanceOf(Text::class, $message);

        $this->expectSocketStreamIsWritable();
        $this->expectSocketStreamWrite();
        $message = $server->binary('Binary');
        $this->assertInstanceOf(Binary::class, $message);

        $this->expectSocketStreamIsWritable();
        $this->expectSocketStreamWrite();
        $message = $server->ping('Ping message');
        $this->assertInstanceOf(Ping::class, $message);

        $this->expectSocketStreamIsWritable();
        $this->expectSocketStreamWrite();
        $message = $server->pong('Pong message');
        $this->assertInstanceOf(Pong::class, $message);

        $this->expectSocketStreamIsWritable();
        $this->expectSocketStreamWrite();
        $message = $server->close(1000, 'Close message');
        $this->assertInstanceOf(Close::class, $message);

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();

        unset($server);
    }

    public function testDetachConnection(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $server->onConnect(function ($server, $connection, $request) {
            $connection->disconnect();
            $server->stop();
        });

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
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
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectSocketStreamIsConnected();
        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamIsConnected()->setReturn(function () use ($server) {
            $server->stop();
            return false;
        });
        $this->expectWsSelectConnections([]);
        $server->start();

        unset($server);
    }

    public function testAlreadyStarted(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $server->onConnect(function ($server, $connection, $request) {
            $connection->disconnect();
            $server->start();
            $server->stop();
        });

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
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
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectSocketStreamIsConnected();

        unset($server);
    }

    public function testCreateServerError(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectStreamFactoryCreateSockerServer()->addAssert(function ($method, $params) {
            throw new StreamException(StreamException::SERVER_SOCKET_ERR, ['uri' => 'test']);
        });
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Server failed to start:');
        $server->start();

        unset($server);
    }

    public function testRunBadOpcodeException(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
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
        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['fake-connection-1']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () use ($server) {
            $server->stop();
            throw new BadOpcodeException();
        });
        $server->start();

        // Should not have closed
        $this->assertEquals(1, $server->getConnectionCount());

        $this->expectSocketStreamClose();
        $this->expectSocketServerClose();
        $this->expectSocketStreamIsConnected();
        $server->disconnect();

        unset($server);
    }

    public function testRunConnectionClosedException(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
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
        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['fake-connection-1']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () use ($server) {
            $server->stop();
            throw new ConnectionClosedException();
        });
        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $server->start();

        // Should be closed
        $this->assertEquals(0, $server->getConnectionCount());

        $this->expectSocketServerClose();
        $server->disconnect();

        unset($server);
    }

    public function testRunServerException(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
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
        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['fake-connection-1']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () use ($server) {
            $server->stop();
            throw new ServerException();
        });
        $server->start();

        // Should not have closed
        $this->assertEquals(1, $server->getConnectionCount());

        $this->expectSocketStreamClose();
        $this->expectSocketServerClose();
        $this->expectSocketStreamIsConnected();
        $server->disconnect();

        unset($server);
    }

    public function testRunExternalException(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
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
        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['fake-connection-1'])->setReturn(function () use ($server) {
            $server->stop();
            throw new StreamException(1000);
        });
        $this->expectSocketStreamClose();
        $this->expectSocketServerClose();
        $this->expectSocketStreamIsConnected();
        $this->expectException(StreamException::class);
        $this->expectExceptionMessage('Stream is detached.');
        $server->start();
    }
}
