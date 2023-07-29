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
    ExpectStreamFactoryTrait,
    StackItem
};
use Phrity\Net\{
    StreamException,
    Uri
};
use WebSocket\{
    Client,
    BadOpcodeException,
    BadUriException,
    ConnectionException,
    TimeoutException
};
use WebSocket\Test\MockStreamTrait;
use WebSocket\Message\Text;

class ClientErrorTest extends TestCase
{
    use ExpectSocketClientTrait;
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

    public function testFailedSocket(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectStreamFactoryCreateSockerClient()->addAssert(function ($method, $params) {
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
            $this->assertEquals(5, $params[0]);
        });
        $this->expectSocketClientSetContext();
        $this->expectSocketClientConnect()->setReturn(function () {
            throw new StreamException(StreamException::CLIENT_CONNECT_ERR, ['uri' => 'tcp://localhost:8000']);
        });
        $this->expectException(ConnectionException::class);
        $this->expectExceptionCode(ConnectionException::CLIENT_CONNECT_ERR);
        $this->expectExceptionMessage('Could not open socket to "tcp://localhost:8000": Server is closed.');
        $client->connect();

        unset($client);
    }

    public function testFailedConnection(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectStreamFactoryCreateSockerClient();
        $this->expectSocketClient();
        $this->expectSocketClientSetPersistent();
        $this->expectSocketClientSetTimeout();
        $this->expectSocketClientSetContext();
        $this->expectSocketClientConnect();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectSocketStreamSetTimeout();
        $this->expectSocketStreamIsConnected()->setReturn(function () {
            return false;
        });
        $this->expectSocketStreamIsConnected()->setReturn(function () {
            return false;
        });
        $this->expectException(ConnectionException::class);
        $this->expectExceptionCode(ConnectionException::CLIENT_CONNECT_ERR);
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
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $this->expectException(BadOpcodeException::class);
        $this->expectExceptionCode(BadOpcodeException::BAD_OPCODE);
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
        $this->expectException(ConnectionException::class);
        $this->expectExceptionCode(ConnectionException::EOF);
        $this->expectExceptionMessage('Could only write 18 out of 22 bytes.');
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
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
        $this->expectException(TimeoutException::class);
        $this->expectExceptionCode(ConnectionException::TIMED_OUT);
        $this->expectExceptionMessage('Connection timeout');
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
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
        $this->expectException(TimeoutException::class);
        $this->expectExceptionCode(TimeoutException::TIMED_OUT);
        $this->expectExceptionMessage('Empty read; connection dead?');
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $client->receive();

        $this->expectSocketStreamClose();
        unset($client);
    }
}
