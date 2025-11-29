<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Exception;

use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\{
    SocketStream,
    StreamFactory,
};
use Phrity\Net\Uri;
use WebSocket\{
    Client,
    Connection,
    Server,
};
use WebSocket\Exception\{
    AbstractException,
    AbstractConnectionException,
    AbstractHandlerException,
    AbstractMessageException,
    BadOpcodeException,
    BadUriException,
    ClientException,
    CloseException,
    ConnectionClosedException,
    ConnectionFailureException,
    ConnectionLevelInterface,
    ConnectionTimeoutException,
    ControlInterface,
    ExceptionInterface,
    HandlerLevelInterface,
    HandshakeException,
    MessageEncodingException,
    MessageLevelInterface,
    ReconnectException,
    ServerException,
};
use WebSocket\Http\Response;
use WebSocket\Test\MockStreamTrait;

/**
 * Test case for WebSocket\Connection: Exceptions.
 */
class ExceptionTest extends TestCase
{
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

    public function testClientException(): void
    {
        $client = new Client('ws://localhost:8000');

        try {
            throw new ClientException();
        } catch (ClientException $e) {
            ;
        }
        $this->assertInstanceOf(ClientException::class, $e);
        $this->assertInstanceOf(AbstractHandlerException::class, $e);
        $this->assertInstanceOf(AbstractException::class, $e);
        $this->assertInstanceOf(HandlerLevelInterface::class, $e);
        $this->assertInstanceOf(ExceptionInterface::class, $e);
        $this->assertEquals('Unspecified handler error', $e->getMessage());
        $this->assertNull($e->getHandler());

        try {
            throw new ClientException($client, 'Test message');
        } catch (ClientException $e) {
            ;
        }
        $this->assertEquals('Test message', $e->getMessage());
        $this->assertSame($client, $e->getHandler());
    }

    public function testServerException(): void
    {
        $server = new Server();

        try {
            throw new ServerException();
        } catch (ServerException $e) {
            ;
        }
        $this->assertInstanceOf(ServerException::class, $e);
        $this->assertInstanceOf(AbstractHandlerException::class, $e);
        $this->assertInstanceOf(AbstractException::class, $e);
        $this->assertInstanceOf(HandlerLevelInterface::class, $e);
        $this->assertInstanceOf(ExceptionInterface::class, $e);
        $this->assertEquals('Unspecified handler error', $e->getMessage());
        $this->assertNull($e->getHandler());

        try {
            throw new ServerException($server, 'Test message');
        } catch (ServerException $e) {
            ;
        }
        $this->assertEquals('Test message', $e->getMessage());
        $this->assertSame($server, $e->getHandler());
    }

    public function testBadUriException(): void
    {
        $client = new Client('ws://localhost:8000');

        try {
            throw new BadUriException($client);
        } catch (BadUriException $e) {
            ;
        }
        $this->assertInstanceOf(BadUriException::class, $e);
        $this->assertInstanceOf(AbstractHandlerException::class, $e);
        $this->assertInstanceOf(AbstractException::class, $e);
        $this->assertInstanceOf(HandlerLevelInterface::class, $e);
        $this->assertInstanceOf(ExceptionInterface::class, $e);
        $this->assertEquals('Bad URI', $e->getMessage());
        $this->assertSame($client, $e->getHandler());

        try {
            throw new BadUriException($client, 'Test message');
        } catch (BadUriException $e) {
            ;
        }
        $this->assertEquals('Test message', $e->getMessage());
    }

    public function testConnectionClosedException(): void
    {
        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000/my/mock/path', streamFactory: new StreamFactory());
        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();
        $connection = $client->getConnection();

        try {
            throw new ConnectionClosedException();
        } catch (ConnectionClosedException $e) {
            ;
        }
        $this->assertInstanceOf(ConnectionClosedException::class, $e);
        $this->assertInstanceOf(AbstractConnectionException::class, $e);
        $this->assertInstanceOf(AbstractException::class, $e);
        $this->assertInstanceOf(ConnectionLevelInterface::class, $e);
        $this->assertInstanceOf(ExceptionInterface::class, $e);
        $this->assertEquals('Connection has unexpectedly closed', $e->getMessage());
        $this->assertNull($e->getConnection());
        $this->assertNull($e->getHandler());

        try {
            throw new ConnectionClosedException($connection, 'Test message');
        } catch (ConnectionClosedException $e) {
            ;
        }
        $this->assertEquals('Test message', $e->getMessage());
        $this->assertSame($connection, $e->getConnection());
        $this->assertSame($client, $e->getHandler());
    }

    public function testConnectionFailureException(): void
    {
        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000/my/mock/path', streamFactory: new StreamFactory());
        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();
        /** @var Connection $connection */
        $connection = $client->getConnection();

        try {
            throw new ConnectionFailureException();
        } catch (ConnectionFailureException $e) {
            ;
        }
        $this->assertInstanceOf(ConnectionFailureException::class, $e);
        $this->assertInstanceOf(AbstractConnectionException::class, $e);
        $this->assertInstanceOf(AbstractException::class, $e);
        $this->assertInstanceOf(ConnectionLevelInterface::class, $e);
        $this->assertInstanceOf(ExceptionInterface::class, $e);
        $this->assertEquals('Connection error', $e->getMessage());
        $this->assertNull($e->getConnection());
        $this->assertNull($e->getHandler());

        try {
            throw new ConnectionFailureException($connection, 'Test message');
        } catch (ConnectionFailureException $e) {
            ;
        }
        $this->assertEquals('Test message', $e->getMessage());
        $this->assertSame($connection, $e->getConnection());
        $this->assertSame($client, $e->getHandler());
    }

    public function testHandshakeException(): void
    {
        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000/my/mock/path', streamFactory: new StreamFactory());
        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();
        /** @var Connection $connection */
        $connection = $client->getConnection();
        $response = new Response();

        try {
            throw new HandshakeException($connection, $response);
        } catch (HandshakeException $e) {
            ;
        }
        $this->assertInstanceOf(HandshakeException::class, $e);
        $this->assertInstanceOf(AbstractConnectionException::class, $e);
        $this->assertInstanceOf(AbstractException::class, $e);
        $this->assertInstanceOf(ConnectionLevelInterface::class, $e);
        $this->assertInstanceOf(ExceptionInterface::class, $e);
        $this->assertEquals('Handshake failed', $e->getMessage());
        $this->assertSame($connection, $e->getConnection());
        $this->assertSame($client, $e->getHandler());
        $this->assertSame($response, $e->getResponse());

        try {
            throw new HandshakeException($connection, $response, 'Test message');
        } catch (HandshakeException $e) {
            ;
        }
        $this->assertEquals('Test message', $e->getMessage());
    }

    public function testBadOpcodeException(): void
    {
        try {
            throw new BadOpcodeException();
        } catch (BadOpcodeException $e) {
            ;
        }
        $this->assertInstanceOf(BadOpcodeException::class, $e);
        $this->assertInstanceOf(AbstractMessageException::class, $e);
        $this->assertInstanceOf(AbstractException::class, $e);
        $this->assertInstanceOf(MessageLevelInterface::class, $e);
        $this->assertInstanceOf(ExceptionInterface::class, $e);
        $this->assertEquals('Bad Opcode', $e->getMessage());

        try {
            throw new BadOpcodeException('Test message');
        } catch (BadOpcodeException $e) {
            ;
        }
        $this->assertEquals('Test message', $e->getMessage());
    }

    public function testConnectionTimeoutException(): void
    {
        try {
            throw new ConnectionTimeoutException();
        } catch (ConnectionTimeoutException $e) {
            ;
        }
        $this->assertInstanceOf(ConnectionTimeoutException::class, $e);
        $this->assertInstanceOf(AbstractMessageException::class, $e);
        $this->assertInstanceOf(AbstractException::class, $e);
        $this->assertInstanceOf(MessageLevelInterface::class, $e);
        $this->assertInstanceOf(ExceptionInterface::class, $e);
        $this->assertEquals('Connection operation timeout', $e->getMessage());

        try {
            throw new ConnectionTimeoutException('Test message');
        } catch (ConnectionTimeoutException $e) {
            ;
        }
        $this->assertEquals('Test message', $e->getMessage());
    }

    public function testMessageEncodingException(): void
    {
        try {
            throw new MessageEncodingException();
        } catch (MessageEncodingException $e) {
            ;
        }
        $this->assertInstanceOf(MessageEncodingException::class, $e);
        $this->assertInstanceOf(AbstractMessageException::class, $e);
        $this->assertInstanceOf(AbstractException::class, $e);
        $this->assertInstanceOf(MessageLevelInterface::class, $e);
        $this->assertInstanceOf(ExceptionInterface::class, $e);
        $this->assertEquals('Message encoding error', $e->getMessage());

        try {
            throw new MessageEncodingException('Test message');
        } catch (MessageEncodingException $e) {
            ;
        }
        $this->assertEquals('Test message', $e->getMessage());
    }

    public function testCloseException(): void
    {
        try {
            throw new CloseException();
        } catch (CloseException $e) {
            ;
        }
        $this->assertInstanceOf(CloseException::class, $e);
        $this->assertInstanceOf(AbstractException::class, $e);
        $this->assertInstanceOf(ControlInterface::class, $e);
        $this->assertInstanceOf(ExceptionInterface::class, $e);
        $this->assertEquals(1000, $e->getCloseStatus());
        $this->assertEquals('Closing connection', $e->getMessage());

        try {
            throw new CloseException(1200, 'Test message');
        } catch (CloseException $e) {
            ;
        }
        $this->assertEquals(1200, $e->getCloseStatus());
        $this->assertEquals('Test message', $e->getMessage());
    }

    public function testReconnectException(): void
    {
        $uri = new Uri('ws://localhost:9000');

        try {
            throw new ReconnectException();
        } catch (ReconnectException $e) {
            ;
        }
        $this->assertInstanceOf(ReconnectException::class, $e);
        $this->assertInstanceOf(AbstractException::class, $e);
        $this->assertInstanceOf(ControlInterface::class, $e);
        $this->assertInstanceOf(ExceptionInterface::class, $e);
        $this->assertNull($e->getUri());
        $this->assertEquals('Reconnect connection', $e->getMessage());

        try {
            throw new ReconnectException($uri, 'Test message');
        } catch (ReconnectException $e) {
            ;
        }
        $this->assertEquals($uri, $e->getUri());
        $this->assertEquals('Test message', $e->getMessage());
    }
}
