<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Server;

use Error;
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
use Phrity\Util\ErrorHandler;
use Psr\Log\NullLogger;
use Stringable;
use WebSocket\{
    Connection,
    Server
};
use WebSocket\Exception\{
    BadOpcodeException,
    CloseException,
    ConnectionClosedException,
    ServerException
};
use WebSocket\Http\{
    Response,
    ServerRequest
};
use WebSocket\Message\{
    Binary,
    Close,
    Ping,
    Pong,
    Text
};
use WebSocket\Middleware\{
    Callback,
    CloseHandler
};
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

    public function testListeners(): void
    {
        $this->expectStreamFactory();
        $server = new Server(8000);
        $server->setStreamFactory(new StreamFactory());
        $handler = new ErrorHandler();
        $this->assertInstanceOf(Stringable::class, $server);
        $this->assertEquals('WebSocket\Server(closed)', "{$server}");

        $server->onHandshake(function ($server, $connection, $request, $response) {
            $this->assertInstanceOf(Server::class, $server);
            $this->assertInstanceOf(Connection::class, $connection);
            $this->assertInstanceOf(ServerRequest::class, $request);
            $this->assertInstanceOf(Response::class, $response);
        });
        $handler->withAll(function () use ($server) {
            $server->onConnect(function ($server, $connection, $request) {
                $this->assertInstanceOf(Server::class, $server);
                $this->assertInstanceOf(Connection::class, $connection);
                $this->assertInstanceOf(ServerRequest::class, $request);
                $server->stop();
            });
        }, function (array $errors) {
            $this->assertEquals(
                'onConnect() is deprecated and will be removed in v4. Use onHandshake() instead.',
                $errors[0]->getMessage()
            );
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
            return base64_decode('gYA=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('48PpGQ==');
        });
        $server->start();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['fake-connection-1']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('goA=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('0NluFQ==');
        });
        $server->start();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['fake-connection-1']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('iIA=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('7DZDMQ==');
        });
        $server->start();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['fake-connection-1']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('iYA=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('TlPnpA==');
        });
        $server->start();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['fake-connection-1']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('ioA=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('QKVFzg==');
        });
        $server->start();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['fake-connection-1']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('g4A=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('ff2Uag==');
        });
        $server->start();

        $this->expectSocketStreamClose();
        $this->expectSocketServerClose();
        $server->disconnect();

        unset($server);
    }

    public function testMiddlewares(): void
    {
        $this->expectStreamFactory();
        $server = new Server(8000);
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

    public function testBroadcastSend(): void
    {
        $this->expectStreamFactory();
        $server = new Server(8000);
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
        $server = new Server(8000);
        $server->setStreamFactory(new StreamFactory());

        $server->onHandshake(function ($server, $connection, $request, $response) {
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

        unset($server);
    }

    public function testShutdown(): void
    {
        $this->expectStreamFactory();
        $server = new Server(8000);
        $server->setStreamFactory(new StreamFactory());
        $server->addMiddleware(new CloseHandler());

        $server->onHandshake(function ($server, $connection, $request, $response) {
            if ($connection->getName() == 'fake-connection-2') {
                $server->shutdown();
            }
        });

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);

        // Accept connection 1
        $this->expectWsSelectConnections(['@server']);
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

        // Accept connection 2
        $this->expectWsSelectConnections(['@server']);
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectSocketStreamGetRemoteName()->setReturn(function () {
            return 'fake-connection-2';
        });
        $this->expectStreamCollectionAttach();
        $this->expectSocketStreamGetLocalName()->setReturn(function () {
            return 'fake-connection-2';
        });
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $this->expectWsServerPerformHandshake();

        // Send close connection 1
        $this->expectSocketStreamIsWritable();
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamIsReadable();
        $this->expectSocketStreamCloseWrite();
        $this->expectSocketStreamGetMetadata();

        // Send close connection 2
        $this->expectSocketStreamIsWritable();
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamIsReadable();
        $this->expectSocketStreamCloseWrite();
        $this->expectSocketStreamGetMetadata();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['fake-connection-1', 'fake-connection-2']);

        // Receive close ack connection 1
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('iIA=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('RExLFw==');
        });
        $this->expectSocketStreamIsWritable();
        $this->expectSocketStreamClose();

        // Receive close ack connection 2
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('iIA=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('RExLFw==');
        });
        $this->expectSocketStreamIsWritable();
        $this->expectSocketStreamClose();

        // Connection detacher
        $this->expectSocketStreamIsConnected();
        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamIsConnected();
        $this->expectStreamCollectionDetach();

        $this->expectSocketServerClose();

        $server->start();
        unset($server);
    }

    public function testShutdownEmpty(): void
    {
        $this->expectStreamFactory();
        $server = new Server(8000);
        $server->setStreamFactory(new StreamFactory());
        $server->addMiddleware(new CloseHandler());

        $server->onTick(function ($server) {
            $server->shutdown();
        });
        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections([]);
        $this->expectSocketServerClose();

        $server->start();
    }

    public function testAlreadyStarted(): void
    {
        $this->expectStreamFactory();
        $server = new Server(8000);
        $server->setStreamFactory(new StreamFactory());

        $server->onHandshake(function ($server, $connection, $request, $response) {
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

        unset($server);
    }

    public function testCreateServerError(): void
    {
        $this->expectStreamFactory();
        $server = new Server(8000);
        $server->setStreamFactory(new StreamFactory());

        $this->expectStreamFactoryCreateSocketServer()->addAssert(function ($method, $params) {
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
        $server = new Server(8000);
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
        $this->assertCount(1, $server->getConnections());
        $this->expectSocketStreamIsReadable();
        $this->assertCount(1, $server->getReadableConnections());
        $this->expectSocketStreamIsWritable();
        $this->assertCount(1, $server->getWritableConnections());

        $this->expectSocketStreamClose();
        $this->expectSocketServerClose();
        $server->disconnect();

        unset($server);
    }

    public function testRunConnectionClosedException(): void
    {
        $this->expectStreamFactory();
        $server = new Server(8000);
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
        $server->start();

        // Should be closed
        $this->assertEquals(0, $server->getConnectionCount());
        $this->assertEmpty($server->getConnections());
        $this->assertEmpty($server->getReadableConnections());
        $this->assertEmpty($server->getWritableConnections());

        $this->expectSocketServerClose();
        $server->disconnect();

        unset($server);
    }

    public function testRunServerException(): void
    {
        $this->expectStreamFactory();
        $server = new Server(8000);
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
        $this->assertCount(1, $server->getConnections());
        $this->expectSocketStreamIsReadable();
        $this->assertCount(1, $server->getReadableConnections());
        $this->expectSocketStreamIsWritable();
        $this->assertCount(1, $server->getWritableConnections());

        $this->expectSocketStreamClose();
        $this->expectSocketServerClose();
        $server->disconnect();

        unset($server);
    }

    public function testRunExternalException(): void
    {
        $this->expectStreamFactory();
        $server = new Server(8000);
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
        $this->expectException(StreamException::class);
        $this->expectExceptionMessage('Stream is detached.');
        $server->start();
    }

    public function testUnmaskedException(): void
    {
        $this->expectStreamFactory();
        $server = new Server(8000);
        $server->setStreamFactory(new StreamFactory());

        $server->onError(function ($server, $connection, $exception) {
            $this->assertInstanceOf(Server::class, $server);
            $this->assertInstanceOf(CloseException::class, $exception);
            $this->assertEquals(1002, $exception->getCloseStatus());
            $this->assertEquals('Masking required', $exception->getMessage());
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

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['fake-connection-1']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('gQA=');
        });
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamIsConnected();
        $server->start();

        $this->expectSocketStreamClose();
        unset($server);
    }

    public function testMaxConnectionsOverflow(): void
    {
        $this->expectStreamFactory();
        $server = new Server(8000);
        $server->setStreamFactory(new StreamFactory());
        $server->setMaxConnections(1);

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['@server']);
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
        $this->expectWsSelectConnections(['@server'])->addAssert(function () use ($server) {
            $server->stop();
        });

        $server->start();
        $this->assertEquals(1, $server->getConnectionCount());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($server);
    }

    public function testUnresolvableError(): void
    {
        $this->expectStreamFactory();
        $server = new Server(8000);
        $server->setStreamFactory(new StreamFactory());

        $server->onTick(function ($server) {
            // Trigger unresolvable error
            $fail = new UnexistingClass();
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
        $this->expectSocketServerClose();
        $this->expectException(Error::class);
        $this->expectExceptionMessage('Class "WebSocket\Test\Server\UnexistingClass" not found');
        $server->start();

        unset($server);
    }
}
