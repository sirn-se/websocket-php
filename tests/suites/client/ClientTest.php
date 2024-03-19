<?php

/**
 * Copyright (C) 2014-2024 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Client;

use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\StreamCollection;
use Phrity\Net\Mock\StreamFactory;
use Phrity\Net\Mock\Stack\{
    ExpectSocketClientTrait,
    ExpectSocketStreamTrait,
    ExpectStreamCollectionTrait,
    ExpectStreamFactoryTrait,
    StackItem
};
use Phrity\Net\StreamException;
use Phrity\Net\Uri;
use Stringable;
use WebSocket\{
    Client,
    Connection
};
use WebSocket\Exception\{
    BadOpcodeException,
    ConnectionClosedException,
    ClientException
};
use WebSocket\Http\{
    Response
};
use WebSocket\Test\MockStreamTrait;
use WebSocket\Message\{
    Binary,
    Close,
    Ping,
    Pong,
    Text
};

class ClientTest extends TestCase
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

    public function testClientSendReceive(): void
    {
        // Creating client
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());
        $this->assertInstanceOf(Stringable::class, $client);

        $this->assertFalse($client->isConnected());
        $this->assertFalse($client->isReadable());
        $this->assertFalse($client->isWritable());
        $this->assertEquals(4096, $client->getFrameSize());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->assertTrue($client->isConnected());
        $this->expectSocketStreamIsReadable();
        $this->assertTrue($client->isReadable());
        $this->expectSocketStreamIsWritable();
        $this->assertTrue($client->isWritable());

        // Sending message
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(23, strlen($params[0]));
        });
        $client->text('Sending a message');

        // Receiving message
        $this->expectSocketStreamIsConnected();
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
        $message = $client->receive();

        $this->expectSocketStreamIsConnected();
        $this->assertTrue($client->isConnected());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testSendMessages(): void
    {
        // Creating client
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->assertTrue($client->isConnected());

        // Sending message
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(23, strlen($params[0]));
        });
        $client->send(new Text('Sending a message'));

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testPayload128(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());
        $client->setFrameSize(65540);

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $payload = file_get_contents(__DIR__ . '/../../mock/payload.128.txt');

        // Sending message
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(136, strlen($params[0]));
        });
        $client->text($payload);

        // Receiving message
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('gX4=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('AIA=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(128, $params[0]);
        })->setReturn(function () use ($payload) {
            return substr($payload, 0, 132);
        });
        $message = $client->receive();

        $this->assertEquals($payload, $message->getContent());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testPayload65536(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());
        $client->setFrameSize(65540);

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $payload = file_get_contents(__DIR__ . '/../../mock/payload.65536.txt');

        // Sending message
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(65550, strlen($params[0]));
        });
        $client->text($payload);

        // Receiving message, multiple read cycles
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('gX8=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(8, $params[0]);
        })->setReturn(function () {
            return base64_decode('AAAAAAABAAA=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(65536, $params[0]);
        })->setReturn(function () use ($payload) {
            return substr($payload, 0, 16374);
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(49162, $params[0]);
        })->setReturn(function () use ($payload) {
            return substr($payload, 16374, 8192);
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(40970, $params[0]);
        })->setReturn(function () use ($payload) {
            return substr($payload, 24566, 8192);
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(32778, $params[0]);
        })->setReturn(function () use ($payload) {
            return substr($payload, 32758, 8192);
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(24586, $params[0]);
        })->setReturn(function () use ($payload) {
            return substr($payload, 40950, 8192);
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(16394, $params[0]);
        })->setReturn(function () use ($payload) {
            return substr($payload, 49142, 8192);
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(8202, $params[0]);
        })->setReturn(function () use ($payload) {
            return substr($payload, 57334, 8192);
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(10, $params[0]);
        })->setReturn(function () use ($payload) {
            return substr($payload, 65526, 10);
        });
        $message = $client->receive();

        $this->assertEquals($payload, $message->getContent());
        $this->assertEquals(65540, $client->getFrameSize());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testMultiFrame(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $client->setFrameSize(8);

        // Sending message
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(14, strlen($params[0]));
            $this->assertEquals('AYg=', base64_encode(substr($params[0], 0, 2)));
        });
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(14, strlen($params[0]));
            $this->assertEquals('AIg=', base64_encode(substr($params[0], 0, 2)));
        });
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(9, strlen($params[0]));
            $this->assertEquals('gIM=', base64_encode(substr($params[0], 0, 2)));
        });
        $client->text('Multi fragment test');

        // Receiving message, multiple frames
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('AYg=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('aR27Eg==');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(8, $params[0]);
        })->setReturn(function () {
            return base64_decode('JGjXZgA93WA=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('AIg=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('3fAuRQ==');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(8, $params[0]);
        })->setReturn(function () {
            return base64_decode('vJdDILOEDjE=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('gIM=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('CTx1wQ==');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(3, $params[0]);
        })->setReturn(function () {
            return base64_decode('bE8B');
        });
        $message = $client->receive();

        $this->assertEquals('Multi fragment test', $message->getContent());
        $this->assertEquals(8, $client->getFrameSize());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testPingPong(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());
        $client->addMiddleware(new \WebSocket\Middleware\PingResponder());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        // Sending ping with content
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(17, strlen($params[0]));
            $this->assertEquals('iYs=', base64_encode(substr($params[0], 0, 2)));
        });
        $client->ping('Server ping');

        // Sending ping without content
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(6, strlen($params[0]));
            $this->assertEquals('iYA=', base64_encode(substr($params[0], 0, 2)));
        });
        $client->ping('');

        // Receiving pong for first ping
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('ios=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('AQEBAQ==');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(11, $params[0]);
        })->setReturn(function () {
            return base64_decode('UmRzd2RzIXFob2Y=');
        });
        $message = $client->receive();

        // Receiving pong for second ping
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('ioA=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('AQEBAQ==');
        });
        $message = $client->receive();

        // Receiving ping
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('iYs=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('tE3AyQ==');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(11, $params[0]);
        })->setReturn(function () {
            return base64_decode('9yGprNo54LndI6c=');
        });
        // Reply to ping
        $this->expectSocketStreamIsWritable();
        $this->expectSocketStreamWrite();
        $message = $client->receive();

        // Receiving text
        $this->expectSocketStreamIsConnected();
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
        $message = $client->receive();
        $this->assertEquals('Receiving a message', $message->getContent());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testRemoteClose(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());
        $client->addMiddleware(new \WebSocket\Middleware\CloseHandler());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        // Receiving close
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('iIk=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('Nk/p9A==');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(9, $params[0]);
        })->setReturn(function () {
            return base64_decode('dSOqmFk8gJpR');
        });
        $this->expectSocketStreamIsWritable();
        $this->expectSocketStreamCloseRead();
        $this->expectSocketStreamGetMetadata();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('iJs='), substr($params[0], 0, 2));
        });
        $this->expectSocketStreamIsReadable();
        $this->expectSocketStreamClose();
        $message = $client->receive();
        $this->assertInstanceOf(Close::class, $message);

        unset($client);
    }

    public function testAutoConnect(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());
        $client->addMiddleware(new \WebSocket\Middleware\CloseHandler());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamIsConnected();
        $client->text('Autoconnect');

        $this->expectSocketStreamIsConnected();
        $this->assertTrue($client->isConnected());

        // Close
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamIsReadable();
        $this->expectSocketStreamCloseWrite();
        $this->expectSocketStreamGetMetadata();
        $client->close();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamRead()->setReturn(function () {
            return base64_decode('iAY==');
        });
        $this->expectSocketStreamRead()->setReturn(function () {
            return base64_decode('A+h0dGZu');
        });
        $this->expectSocketStreamIsWritable();
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $message = $client->receive();
        $this->assertInstanceOf(Close::class, $message);

        $this->expectSocketStreamIsConnected();
        $this->assertFalse($client->isConnected());

        // Implicit reconnect and handshake, receive message
        $this->expectSocketStreamIsConnected();
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
        $this->expectSocketClientConnect();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectSocketStreamGetRemoteName();
        $this->expectStreamCollectionAttach();
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(60, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake();
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
        $message = $client->receive();

        $this->expectSocketStreamIsConnected();
        $this->assertTrue($client->isConnected());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testFrameFragmentation(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());
        $client->addMiddleware(new \WebSocket\Middleware\CloseHandler());
        $client->addMiddleware(new \WebSocket\Middleware\PingResponder());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        // Receiving non-final text frame
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('AYg=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('aR27Eg==');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(8, $params[0]);
        })->setReturn(function () {
            return base64_decode('JGjXZgA93WA=');
        });
        // Receiving final pong frame
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('ios=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('AQEBAQ==');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(11, $params[0]);
        })->setReturn(function () {
            return base64_decode('UmRzd2RzIXFob2Y=');
        });
        $message = $client->receive();
        $this->assertEquals('Server ping', $message->getContent());

        // Receiving non-final continuation frame for text
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('AIg=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('3fAuRQ==');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(8, $params[0]);
        })->setReturn(function () {
            return base64_decode('vJdDILOEDjE=');
        });
        // Receiving final continuation frame for text
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('gIM=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('CTx1wQ==');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(3, $params[0]);
        })->setReturn(function () {
            return base64_decode('bE8B');
        });
        $message = $client->receive();
        $this->assertEquals('Multi fragment test', $message->getContent());

        // Receive close message
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('iIk=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('Nk/p9A==');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(9, $params[0]);
        })->setReturn(function () {
            return base64_decode('dSOqmFk8gJpR');
        });
        $this->expectSocketStreamIsWritable();
        $this->expectSocketStreamCloseRead();
        $this->expectSocketStreamGetMetadata();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('iJs='), substr($params[0], 0, 2));
        });
        $this->expectSocketStreamIsReadable();
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $message = $client->receive();
        $this->assertInstanceOf(Close::class, $message);

        $this->assertEquals('Closing', $message->getContent());
        $this->assertFalse($client->isConnected());

        unset($client);
    }

    public function testConvenicanceMethods(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());
        $client->addMiddleware(new \WebSocket\Middleware\CloseHandler());
        $client->addMiddleware(new \WebSocket\Middleware\PingResponder());

        $this->assertNull($client->getName());
        $this->assertNull($client->getRemoteName());
        $this->assertNull($client->getMeta('metadata'));
        $this->assertEquals('WebSocket\Client(closed)', "{$client}");

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        // Send "text"
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals('gYc=', base64_encode(substr($params[0], 0, 2)));
        });
        $message = $client->text('Connect');
        $this->assertInstanceOf(Text::class, $message);

        // Send "binary"
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals('gpQ=', base64_encode(substr($params[0], 0, 2)));
        });
        $message = $client->binary(base64_encode('Binary content'));
        $this->assertInstanceOf(Binary::class, $message);

        // Send "ping"
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals('iYA=', base64_encode(substr($params[0], 0, 2)));
        });
        $message = $client->ping();
        $this->assertInstanceOf(Ping::class, $message);

        // Send "pong"
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals('ioA=', base64_encode(substr($params[0], 0, 2)));
        });
        $message = $client->pong();
        $this->assertInstanceOf(Pong::class, $message);

        // Send "close"
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals('iIY=', base64_encode(substr($params[0], 0, 2)));
        });
        $this->expectSocketStreamIsReadable();
        $this->expectSocketStreamCloseWrite();
        $this->expectSocketStreamGetMetadata();
        $message = $client->close();
        $this->assertInstanceOf(Close::class, $message);

        // Test names
        $this->expectSocketStreamIsConnected();
        $this->assertEquals('127.0.0.1:12345', $client->getName());

        $this->expectSocketStreamIsConnected();
        $this->assertEquals('localhost:8000', $client->getRemoteName());

        $this->expectSocketStreamIsConnected();
        $this->assertEquals('WebSocket\Client(ws://localhost:8000/my/mock/path)', "{$client}");

        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testDisconnect(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $client->disconnect();

        $client->disconnect();
    }

    public function testListeners(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $client->onConnect(function ($client, $connection, $response) {
            $this->assertInstanceOf(Client::class, $client);
            $this->assertInstanceOf(Connection::class, $connection);
            $this->assertInstanceOf(Response::class, $response);
            $this->assertTrue($client->isRunning());
            $client->stop();
        });
        $client->onText(function ($client, $connection, $message) {
            $this->assertInstanceOf(Client::class, $client);
            $this->assertInstanceOf(Connection::class, $connection);
            $this->assertInstanceOf(Text::class, $message);
            $client->stop();
        });
        $client->onBinary(function ($client, $connection, $message) {
            $this->assertInstanceOf(Client::class, $client);
            $this->assertInstanceOf(Connection::class, $connection);
            $this->assertInstanceOf(Binary::class, $message);
            $client->stop();
        });
        $client->onPing(function ($client, $connection, $message) {
            $this->assertInstanceOf(Client::class, $client);
            $this->assertInstanceOf(Connection::class, $connection);
            $this->assertInstanceOf(Ping::class, $message);
            $client->stop();
        });
        $client->onPong(function ($client, $connection, $message) {
            $this->assertInstanceOf(Client::class, $client);
            $this->assertInstanceOf(Connection::class, $connection);
            $this->assertInstanceOf(Pong::class, $message);
            $client->stop();
        });
        $client->onClose(function ($client, $connection, $message) {
            $this->assertInstanceOf(Client::class, $client);
            $this->assertInstanceOf(Connection::class, $connection);
            $this->assertInstanceOf(Close::class, $message);
            $client->stop();
        });
        $client->onDisconnect(function ($client, $connection) {
            $this->assertInstanceOf(Client::class, $client);
            $this->assertInstanceOf(Connection::class, $connection);
            $client->stop();
        });
        $client->onError(function ($client, $connection, $exception) {
            $this->assertInstanceOf(Client::class, $client);
            $this->assertInstanceOf(BadOpcodeException::class, $exception);
            $client->stop();
        });
        $client->onTick(function ($client) {
            $this->assertInstanceOf(Client::class, $client);
        });

        $this->assertFalse($client->isRunning());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->start();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['localhost:8000']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('gQA=');
        });
        $this->expectSocketStreamIsConnected();
        $client->start();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['localhost:8000']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('ggA=');
        });
        $this->expectSocketStreamIsConnected();
        $client->start();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['localhost:8000']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('ggA=');
        });
        $this->expectSocketStreamIsConnected();
        $client->start();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['localhost:8000']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('iQA=');
        });
        $this->expectSocketStreamIsConnected();
        $client->start();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['localhost:8000']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('igA=');
        });
        $this->expectSocketStreamIsConnected();
        $client->start();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['localhost:8000']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('iAA=');
        });
        $this->expectSocketStreamIsConnected();
        $client->start();

        $this->expectSocketStreamIsConnected();
        $this->expectWsSelectConnections(['localhost:8000']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('gwA=');
        });
        $this->expectSocketStreamIsConnected();
        $client->start();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testAlreadyStarted(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $client->onConnect(function ($client, $connection, $request) {
            $client->start();
            $client->stop();
        });
        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $this->expectSocketStreamIsConnected();
        $client->start();

        $this->expectSocketStreamClose();
        unset($server);
    }

    public function testRunConnectionClosedException(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $client->onText(function ($client, $connection, $message) {
            $this->assertInstanceOf(Client::class, $client);
            $this->assertInstanceOf(Connection::class, $connection);
            $this->assertInstanceOf(Text::class, $message);
            $client->stop();
        });

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $this->expectWsSelectConnections(['localhost:8000']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () use ($client) {
            throw new ConnectionClosedException();
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $client->start();

        unset($client);
    }

    public function testRunClientException(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $client->onText(function ($client, $connection, $message) {
            $this->assertInstanceOf(Client::class, $client);
            $this->assertInstanceOf(Connection::class, $connection);
            $this->assertInstanceOf(Text::class, $message);
            $client->stop();
        });

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $this->expectWsSelectConnections(['localhost:8000']);
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () use ($client) {
            throw new ClientException();
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->start();
        unset($client);
    }

    public function testRunExternalException(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $this->expectWsSelectConnections(['localhost:8000'])->setReturn(function () {
            throw new StreamException(1000);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $this->expectException(StreamException::class);
        $this->expectExceptionMessage('Stream is detached.');
        $client->start();

        unset($client);
    }
}
