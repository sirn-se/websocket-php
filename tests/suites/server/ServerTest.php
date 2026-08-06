<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Server;

use Error;
use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\StreamCollection;
use Phrity\Net\Mock\StreamFactory;
use Phrity\Net\Mock\Stack\{
    ExpectContextTrait,
    ExpectSocketServerTrait,
    ExpectSocketStreamTrait,
    ExpectStreamCollectionTrait,
    ExpectStreamFactoryTrait
};
use Phrity\Net\StreamException;
use Phrity\Util\ErrorHandler;
use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface,
};
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

    public function testListeners(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $handler = new ErrorHandler();
        $this->assertInstanceOf(Stringable::class, $server);
        $this->assertEquals('WebSocket\Server(closed)', "{$server}");
        $this->assertEquals('server/8000', $server->getIdentity());

        $server->onHandshake(function ($server, $connection, $request, $response) {
            $this->assertInstanceOf(Server::class, $server);
            $this->assertInstanceOf(Connection::class, $connection);
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
            $this->assertInstanceOf(ResponseInterface::class, $response);
        });
        $handler->withAll(function () use ($server) {
            $server->onConnect(function ($server, $connection, $request) {
                $this->assertInstanceOf(Server::class, $server);
                $this->assertInstanceOf(Connection::class, $connection);
                $this->assertInstanceOf(ServerRequestInterface::class, $request);
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
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept();
        $this->expectWsServerPerformHandshake();
        $server->start();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['*/connection/8000/12345']);
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
        $this->expectWsSelectConnections(['*/connection/8000/12345']);
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
        $this->expectWsSelectConnections(['*/connection/8000/12345']);
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
        $this->expectWsSelectConnections(['*/connection/8000/12345']);
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
        $this->expectWsSelectConnections(['*/connection/8000/12345']);
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
        $this->expectWsSelectConnections(['*/connection/8000/12345']);
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
        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testMiddlewares(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $server->addMiddleware(new Callback());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept()->addAssert(function ($method, $params) use ($server) {
            $server->stop();
        });
        $this->expectWsServerPerformHandshake();
        $server->start();

        $server->addMiddleware(new Callback());

        $this->expectSocketStreamClose();
        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testBroadcastSend(): void
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

        $this->expectSocketStreamClose();
        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testDetachConnection(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $server->onHandshake(function ($server, $connection, $request, $response) {
            $connection->disconnect();
            $server->stop();
        });

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept();
        $this->expectWsServerPerformHandshake();
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectSocketStreamIsConnected();
        $this->expectStreamCollectionDetach();
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept();
        $this->expectWsServerPerformHandshake();
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectSocketStreamClose();
        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testShutdown(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());
        $server->addMiddleware(new CloseHandler());

        $server->onHandshake(function ($server, $connection, $request, $response) {
            if ($connection->getIdentity() == '*/connection/8000/23456') {
                $server->shutdown();
            }
        });

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);

        // Accept connection 1
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept(remote: '127.0.0.1:12345');
        $this->expectWsServerPerformHandshake();
        $this->expectSocketStreamIsConnected();

        // Accept connection 2
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept(remote: '127.0.0.1:23456');
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
        // The @server handler should be blocked now
        $this->expectWsSelectConnections(['server/8000', '*/connection/8000/12345', '*/connection/8000/23456']);

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

        $this->expectSocketStreamIsConnected();
        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamIsConnected();
        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->start();

        $server->disconnect();
    }

    public function testShutdownEmpty(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());
        $server->addMiddleware(new CloseHandler());

        $server->onTick(function ($server) {
            $server->shutdown();
        });
        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections([]);
        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->start();

        $server->disconnect();
    }

    public function testAlreadyStarted(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $server->onHandshake(function ($server, $connection, $request, $response) {
            $connection->disconnect();
            $server->start();
            $server->stop();
        });

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept();
        $this->expectWsServerPerformHandshake();
        $this->expectSocketStreamClose();
        $server->start();

        $this->expectSocketStreamClose();
        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testCreateServerError(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectStreamFactoryCreateSocketServer()->addAssert(function ($method, $params) {
            throw new StreamException(StreamException::SERVER_SOCKET_ERR, ['uri' => 'test']);
        });
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Server failed to start:');
        $server->start();
    }

    public function testServerAccessError(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectSocketServerAccept()->addAssert(function ($method, $params) use ($server) {
            $server->stop();
            throw new StreamException(StreamException::SERVER_ACCEPT_ERR, ['uri' => 'test']);
        });
        $server->start();

        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testRunBadOpcodeException(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept();
        $this->expectWsServerPerformHandshake();
        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['*/connection/8000/12345']);
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
        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testRunConnectionClosedException(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept();
        $this->expectWsServerPerformHandshake();
        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['*/connection/8000/12345']);
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
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testRunConnectionClosedExceptionDispatchesDisconnect(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $disconnected = [];
        $server->onDisconnect(function ($server, $connection) use (&$disconnected) {
            $disconnected[] = $connection->getIdentity();
        });

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept();
        $this->expectWsServerPerformHandshake();
        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['*/connection/8000/12345']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () use ($server) {
            $server->stop();
            throw new ConnectionClosedException();
        });
        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamClose();
        $server->start();

        // Should be removed and dispatched as disconnected
        $this->assertEquals(0, $server->getConnectionCount());
        $this->assertEquals(['*/connection/8000/12345'], $disconnected);

        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testRunServerException(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept();
        $this->expectWsServerPerformHandshake();
        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['*/connection/8000/12345']);
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
        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testRunExternalException(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept();
        $this->expectWsServerPerformHandshake();
        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['*/connection/8000/12345'])->setReturn(function () use ($server) {
            $server->stop();
            throw new StreamException(1000);
        });
        $this->expectSocketStreamClose();
        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $this->expectException(StreamException::class);
        $this->expectExceptionMessage('Stream is detached.');
        $server->start();
    }

    public function testUnmaskedException(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $server->onError(function ($server, $connection, $exception) {
            $this->assertInstanceOf(Server::class, $server);
            $this->assertInstanceOf(CloseException::class, $exception);
            $this->assertEquals(1002, $exception->getCloseStatus());
            $this->assertEquals('Masking required', $exception->getMessage());
            $server->stop();
        });

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept();
        $this->expectWsServerPerformHandshake();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['*/connection/8000/12345']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('gQA=');
        });
        $this->expectSocketStreamWrite();
        $server->start();

        $this->expectSocketStreamClose();
        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testMaxConnectionsOverflow(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());
        $server->setMaxConnections(1);

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept();
        $this->expectWsServerPerformHandshake();
        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['server/8000'])->addAssert(function () use ($server) {
            $server->stop();
        });

        $server->start();
        $this->assertEquals(1, $server->getConnectionCount());

        $this->expectSocketStreamClose();
        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $server->disconnect();
    }

    public function testUnresolvableError(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $server->onTick(function ($server) {
            /**
             * Trigger unresolvable error
             * @phpstan-ignore class.notFound
             */
            $fail = new UnexistingClass();
        });

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectWsSelectConnections(['server/8000']);
        $this->expectWsServerAccept();
        $this->expectWsServerPerformHandshake();
        $this->expectSocketStreamClose();
        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $this->expectStreamCollectionDetach();
        $this->expectException(Error::class);
        $this->expectExceptionMessage('Class "WebSocket\Test\Server\UnexistingClass" not found');
        $server->start();
    }

    public function testDeprecatedSetStreamFactory(): void
    {
        $this->expectStreamFactory();
        $server = new Server(8000);

        $errorHandler = new ErrorHandler();
        $errorHandler->withAll(function () use ($server) {
            $factory = new StreamFactory();
            $this->assertSame($server, $server->setStreamFactory($factory));
        }, function (array $errors) {
            $this->assertEquals(
                'Server.setStreamFactory is deprecated and will be removed in v4.',
                $errors[0]->getMessage()
            );
        });

        $server->disconnect();
    }
}
