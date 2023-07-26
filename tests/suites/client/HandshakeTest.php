<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

declare(strict_types=1);

namespace WebSocket\Test\Client;

use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\StreamFactory;
use Phrity\Net\Mock\Stack\{
    ExpectSocketClientTrait,
    ExpectSocketStreamTrait,
    ExpectStreamFactoryTrait
};
use Phrity\Net\StreamException;
use WebSocket\{
    Client,
    ConnectionException
};
use WebSocket\Http\Response;
use WebSocket\Test\MockStreamTrait;

/**
 * Test case for WebSocket\Client: Handshake.
 */
class HandshakeTest extends TestCase
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

    public function testHandshakeResponse(): void
    {
        // Creating client
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->assertFalse($client->isConnected());
        $this->assertEquals(4096, $client->getFragmentSize());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $response = $client->getHandshakeResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(101, $response->getStatusCode());
        $this->assertEquals('Switching Protocols', $response->getReasonPhrase());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testHandshakeConnectionFailure(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamReadLine()->setReturn(function () {
            throw new StreamException(StreamException::FAIL_READ);
        });
        $this->expectException(ConnectionException::class);
        $this->expectExceptionCode(ConnectionException::CLIENT_HANDSHAKE_ERR);
        $this->expectExceptionMessage('Client handshake error');
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->connect();

        unset($client);
    }

    public function testHandshakeUpgradeStatusFailure(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 200 OK\r\n\r\n";
        });
        $this->expectException(ConnectionException::class);
        $this->expectExceptionCode(ConnectionException::CLIENT_HANDSHAKE_ERR);
        $this->expectExceptionMessage('Invalid status code 200.');
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->connect();

        unset($client);
    }

    public function testHandshakeUpgradeHeadersFailure(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nInvalid upgrade\r\n\r\n";
        });
        $this->expectException(ConnectionException::class);
        $this->expectExceptionCode(ConnectionException::CLIENT_HANDSHAKE_ERR);
        $this->expectExceptionMessage('Connection to \'ws://localhost:8000/my/mock/path\' failed');
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->connect();

        unset($client);
    }

    public function testHandshakeUpgradeKeyFailure(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\n"
                . "Sec-WebSocket-Accept: BAD_KEY\r\n\r\n";
        });
        $this->expectException(ConnectionException::class);
        $this->expectExceptionCode(ConnectionException::CLIENT_HANDSHAKE_ERR);
        $this->expectExceptionMessage('Server sent bad upgrade response');
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->connect();

        unset($client);
    }
}
