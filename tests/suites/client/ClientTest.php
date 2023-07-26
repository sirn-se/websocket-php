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
use Phrity\Util\ErrorHandler;
use WebSocket\{
    Client,
    BadOpcodeException,
    BadUriException,
    ConnectionException,
    TimeoutException
};
use WebSocket\Http\Response;
use WebSocket\Test\{
    MockStreamTrait,
    MockUri
};
use WebSocket\Message\{
    Close,
    Text
};

class ClientTest extends TestCase
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

// Local close
// Remote close

    /**
     * Test masked, explicit: connect, write, read, close
     */
    public function testClientSendReceive(): void
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

        $this->expectSocketStreamIsConnected();
        $this->assertTrue($client->isConnected());

        // Sending message
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(23, strlen($params[0]));
        });
        $client->text('Sending a message');

        $response = $client->getHandshakeResponse();
        $this->assertInstanceOf(Response::class, $response);

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
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());
        $client->setFragmentSize(65540);

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
        $client->setFragmentSize(65540);

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
        $this->assertEquals(65540, $client->getFragmentSize());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testMultiFragment(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $client->setFragmentSize(8);

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
        $this->assertEquals(8, $client->getFragmentSize());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testPingPong(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

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
        $this->expectSocketStreamIsConnected();
        $message = $client->receive();
        $this->assertInstanceOf(Close::class, $message);

        unset($client);
    }

    public function testAutoConnect(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamIsConnected();
        $client->text('Autoconnect');

        $this->expectSocketStreamIsConnected();
        $this->assertTrue($client->isConnected());

        // Close
        $this->expectSocketStreamIsConnected();
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
        $this->expectStreamFactoryCreateSockerClient()->addAssert(function ($method, $params) {
            $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
            $this->assertEquals('tcp://localhost:8000', "{$params[0]}");
        });
        $this->expectSocketClient()->addAssert(function ($method, $params) {
            $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
            $this->assertEquals('tcp://localhost:8000', "{$params[0]}");
        });
        $this->expectSocketClientSetPersistent()->addAssert(function ($method, $params) {
            $this->assertFalse($params[0]);
        });
        $this->expectSocketClientSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
        });
        $this->expectSocketClientSetContext();
        $this->expectSocketClientConnect();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
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

    public function testFailedSocket(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectStreamFactoryCreateSockerClient()->addAssert(function ($method, $params) {
            $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
            $this->assertEquals('tcp://localhost:8000', "{$params[0]}");
        });
        $this->expectSocketClient()->addAssert(function ($method, $params) {
            $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
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

    public function testFrameFragmentation(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

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
        $this->expectSocketStreamIsConnected();
        $this->assertFalse($client->isConnected());

        unset($client);
    }

    public function testConvenicanceMethods(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path', ['masked' => false]);
        $client->setStreamFactory(new StreamFactory());

        $this->assertNull($client->getName());
        $this->assertNull($client->getRemoteName());
        $this->assertEquals('WebSocket\Client(closed)', "{$client}");

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        // Send "text"
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals('gYc=', base64_encode(substr($params[0], 0, 2)));
        });
        $client->text('Connect');

        // Send "binary"
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals('gpQ=', base64_encode(substr($params[0], 0, 2)));
        });
        $client->binary(base64_encode('Binary content'));

        // Send "ping"
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals('iYA=', base64_encode(substr($params[0], 0, 2)));
        });
        $client->ping();

        // Send "pong"
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals('ioA=', base64_encode(substr($params[0], 0, 2)));
        });
        $client->pong();

        // Test names
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamGetLocalName()->setReturn(function () {
            return '127.0.0.1:12345';
        });
        $this->assertEquals('127.0.0.1:12345', $client->getName());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamGetRemoteName()->setReturn(function () {
            return '127.0.0.1:8000';
        });
        $this->assertEquals('127.0.0.1:8000', $client->getRemoteName());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamGetLocalName()->setReturn(function () {
            return '127.0.0.1:12345';
        });
        $this->assertEquals('WebSocket\Client(127.0.0.1:12345)', "{$client}");

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }
}
