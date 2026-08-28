<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Client;

use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\StreamFactory;
use Phrity\Net\Mock\Stack\{
    ExpectSocketClientTrait,
    ExpectSocketStreamTrait,
    ExpectStreamCollectionTrait,
    ExpectStreamFactoryTrait
};
use Phrity\Net\StreamException;
use Phrity\Net\Uri;
use Psr\Http\Message\ResponseInterface;
use WebSocket\Client;
use WebSocket\Exception\{
    BadOpcodeException,
    BadUriException,
    ClientException,
    ConnectionClosedException,
    ConnectionFailureException,
    ConnectionTimeoutException,
    HandshakeException,
    ReconnectException,
};
use WebSocket\Test\MockStreamTrait;

/**
 * Test case for WebSocket\Client: Handshake.
 */
class HandshakeTest extends TestCase
{
    use ExpectSocketClientTrait;
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

    public function testHandshakeResponse(): void
    {
        // Creating client
        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000/my/mock/path', streamFactory: new StreamFactory());

        $this->assertFalse($client->isConnected());
        $this->assertEquals(4096, $client->getFrameSize());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $response = $client->getHandshakeResponse();
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(101, $response->getStatusCode());
        $this->assertEquals('Switching Protocols', $response->getReasonPhrase());

        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->disconnect();
    }

    public function testHandshakeResponseVariant(): void
    {
        // Creating client
        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000/my/mock/path', streamFactory: new StreamFactory());

        $this->assertFalse($client->isConnected());
        $this->assertEquals(4096, $client->getFrameSize());

        $this->expectWsClientConnect();
        $this->expectSocketStreamWrite()->addAssert(
            function (string $method, array $params): void {
                preg_match('/Sec-WebSocket-Key: ([\S]*)\r\n/', $params[0], $m);
                $this->lastWsKey = $m[1] ?? '';
            }
        );
        $this->expectSocketStreamReadLine()->setReturn(function (array $params) {
            return "HTTP/1.1 101 Switching Protocols\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function (array $params) {
            return "Upgrade: websocket\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function (array $params) {
            return "Connection: keep-alive, upgrade\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function (array $params) {
            $wsKeyRes = base64_encode(pack('H*', sha1($this->lastWsKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
            return "Sec-WebSocket-Accept: {$wsKeyRes}\r\n\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function (array $params) {
            return "\r\n";
        });
        $client->connect();

        $response = $client->getHandshakeResponse();
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(101, $response->getStatusCode());
        $this->assertEquals('Switching Protocols', $response->getReasonPhrase());

        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->disconnect();
    }

    public function testHandshakeConnectionFailure(): void
    {
        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000/my/mock/path', streamFactory: new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamReadLine()->setReturn(function () {
            throw new StreamException(StreamException::FAIL_READ);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamGetMetadata();
        $this->expectException(ConnectionFailureException::class);
        $this->expectExceptionMessage('Connection error');
        $client->connect();
    }

    public function testHandshakeUpgradeStatusFailure(): void
    {
        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000/my/mock/path', streamFactory: new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 200 OK\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $this->expectException(HandshakeException::class);
        $this->expectExceptionMessage('Invalid status code 200');
        $client->connect();
    }

    public function testHandshakeUpgradeHeadersFailure(): void
    {
        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000/my/mock/path', streamFactory: new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 101 Switching Protocols\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Upgrade: Invalid upgrade\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $this->expectException(HandshakeException::class);
        $this->expectExceptionMessage('Connection to ws://localhost:8000/my/mock/path failed');
        $client->connect();
    }

    public function testHandshakeUpgradeKeyFailure(): void
    {
        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000/my/mock/path', streamFactory: new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 101 Switching Protocols\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Upgrade: websocket\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Accept: BAD_KEY\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $this->expectException(HandshakeException::class);
        $this->expectExceptionMessage('Server sent bad upgrade response');
        $client->connect();
    }

    public function testHandshakeReconnect(): void
    {
        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000/my/mock/path', streamFactory: new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamReadLine()->setReturn(function () {
            throw new ReconnectException(context: ['uri' => new Uri('ws://localhost:8000/my/new/path')]);
        });
        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();

        $this->expectWsClientConnect(local: '127.0.0.1:12346');
        $this->expectWsClientPerformHandshake(path: '/my/new/path');
        $client->connect();

        $response = $client->getHandshakeResponse();
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(101, $response->getStatusCode());
        $this->assertEquals('Switching Protocols', $response->getReasonPhrase());

        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->disconnect();
    }
}
