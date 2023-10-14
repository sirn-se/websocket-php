<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

declare(strict_types=1);

namespace WebSocket\Test\Client;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\StreamFactory;
use Phrity\Net\Mock\Stack\{
    ExpectSocketClientTrait,
    ExpectSocketStreamTrait,
    ExpectStreamCollectionTrait,
    ExpectStreamFactoryTrait,
    StackItem
};
use Phrity\Net\{
    StreamException,
    Uri
};
use WebSocket\Client;
use WebSocket\Exception\{
    BadOpcodeException,
    BadUriException,
    ClientException,
    ConnectionClosedException,
    ConnectionTimeoutException,
    HandshakeException
};
use WebSocket\Test\MockStreamTrait;
use WebSocket\Message\Text;

class ClientErrorTest extends TestCase
{
    use ExpectSocketClientTrait;
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

    public function testFailedSocket(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectStreamFactoryCreateStreamCollection();
        $this->expectStreamCollection();
        $this->expectStreamFactoryCreateSocketClient()->addAssert(function ($method, $params) {
            $this->assertInstanceOf(Uri::class, $params[0]);
            $this->assertEquals('tcp://localhost:8000', "{$params[0]}");
        });
        $this->expectSocketClient()->addAssert(function ($method, $params) {
            $this->assertInstanceOf(Uri::class, $params[0]);
            $this->assertEquals('tcp://localhost:8000', "{$params[0]}");
        });
        $this->expectSocketClientSetPersistent()->addAssert(function ($method, $params) {
            $this->assertFalse($params[0]);
        });
        $this->expectSocketClientSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(60, $params[0]);
        });
        $this->expectSocketClientSetContext();
        $this->expectSocketClientConnect()->setReturn(function () {
            throw new StreamException(StreamException::CLIENT_CONNECT_ERR, ['uri' => 'tcp://localhost:8000']);
        });
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Could not open socket to "tcp://localhost:8000": Client could not connect');

        $client->connect();

        unset($client);
    }

    public function testFailedConnection(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectStreamFactoryCreateStreamCollection();
        $this->expectStreamCollection();
        $this->expectStreamFactoryCreateSocketClient();
        $this->expectSocketClient();
        $this->expectSocketClientSetPersistent();
        $this->expectSocketClientSetTimeout();
        $this->expectSocketClientSetContext();
        $this->expectSocketClientConnect();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectSocketStreamGetRemoteName();
        $this->expectStreamCollectionAttach();
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $this->expectSocketStreamIsConnected()->setReturn(function () {
            return false;
        });
        $this->expectSocketStreamIsConnected()->setReturn(function () {
            return false;
        });
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Invalid stream on "tcp://localhost:8000".');
        $client->connect();

        unset($client);
    }

    public function testRecieveBadOpcode(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('jww=');
        });
        $this->expectSocketStreamRead()->setReturn(function () {
            return 'Test message';
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $this->expectException(BadOpcodeException::class);
        $this->expectExceptionMessage("Invalid opcode '15' provided");
        $message = $client->receive();

        unset($client);
    }

    public function testBrokenWrite(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->setReturn(function () {
            return 18;
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamGetMetadata()->setReturn(function () {
            return ['eof' => true, 'mode' => 'rw', 'seekable' => false];
        });
        $this->expectException(ConnectionClosedException::class);
        $this->expectExceptionMessage('Connection has unexpectedly closed');
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->text('Failing to write');

        unset($client);
    }

    public function testReadTimeout(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamRead()->setReturn(function () {
            throw new StreamException(StreamException::FAIL_READ);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamGetMetadata()->setReturn(function () {
            return ['timed_out' => true, 'mode' => 'rw', 'seekable' => false];
        });
        $this->expectException(ConnectionTimeoutException::class);
        $this->expectExceptionMessage('Connection operation timeout');
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->receive();

        unset($client);
    }

    public function testEmptyRead(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamRead()->setReturn(function () {
            return '';
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamGetMetadata()->setReturn(function () {
            return ['timed_out' => true, 'mode' => 'rw', 'seekable' => false];
        });
        $this->expectException(ConnectionTimeoutException::class);
        $this->expectExceptionMessage('Connection operation timeout');
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->receive();

        $this->expectSocketStreamClose();
        unset($client);
    }
}
