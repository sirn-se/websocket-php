<?php

/**
 * Test case for Client.
 * Note that this test is performed by mocking socket/stream calls.
 */

declare(strict_types=1);

namespace WebSocket\Test\Client;

use ErrorException;
use Phrity\Net\Mock\Mock;
use Phrity\Net\Mock\StreamFactory;
use Phrity\Net\StreamException;
use Phrity\Net\Uri;
use Phrity\Util\ErrorHandler;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Phrity\Net\Mock\Stack\{
    ExpectSocketClientTrait,
    ExpectSocketStreamTrait,
    ExpectStreamFactoryTrait,
    StackItem
};
use WebSocket\{
    Client,
    BadOpcodeException
};
use WebSocket\Test\MockStreamTrait;

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

    /**
     * Test masked, explicit: connect, write, read, close
     */
    public function testClientMasked(): void
    {
        // Creating client
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->assertFalse($client->isConnected());
        $this->assertEquals(null, $client->getLastOpcode());
        $this->assertEquals(4096, $client->getFragmentSize());

        // Explicit connect and handshake
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->assertTrue($client->isConnected());

        // Sending message
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(23, strlen($params[0]));
        });
        $client->send('Sending a message');

        $this->assertEquals(null, $client->getLastOpcode());

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

        $this->assertEquals('text', $client->getLastOpcode());
        $this->expectSocketStreamIsConnected();
        $this->assertTrue($client->isConnected());
        $this->assertNull($client->getCloseStatus());

        // Close
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(12, strlen($params[0]));
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('iJo=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('YvrScQ==');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(26, $params[0]);
        })->setReturn(function () {
            return base64_decode('YRKRHQ2Jt1EDmbkfDY2+FAadtxVY2uNBUso=');
        });
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $client->close();

        $this->expectSocketStreamIsConnected();
        $this->assertFalse($client->isConnected());
        $this->assertEquals(1000, $client->getCloseStatus());

        unset($client);
    }

    public function testClientExtendedUrl(): void
    {
        // Creating client
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path?my_query=yes#my_fragment');
        $client->setStreamFactory(new StreamFactory());

        // Explicit connect and handshake
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake('localhost:8000', '/my/mock/path?my_query=yes');
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testClientNoPath(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000');
        $client->setStreamFactory(new StreamFactory());

        // Explicit connect and handshake
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake('localhost:8000', '/');
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testClientRelativePath(): void
    {
        $uri = new Uri('ws://localhost:8000');
        $uri = $uri->withPath('my/mock/path');

        $this->expectStreamFactory();
        $client = new Client($uri);
        $client->setStreamFactory(new StreamFactory());

        // Explicit connect and handshake
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake('localhost:8000', '/my/mock/path');
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testClientWsDefaultPort(): void
    {
        $uri = new Uri('ws://localhost');
        $uri = $uri->withPath('my/mock/path');

        $this->expectStreamFactory();
        $client = new Client($uri);
        $client->setStreamFactory(new StreamFactory());

        // Explicit connect and handshake
        $this->expectStreamFactoryCreateSockerClient()->addAssert(function ($method, $params) {
            $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
            $this->assertEquals('tcp://localhost:80', "{$params[0]}");
        });
        $this->expectSocketClient()->addAssert(function ($method, $params) {
            $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
            $this->assertEquals('tcp://localhost:80', "{$params[0]}");
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake('localhost:80', '/my/mock/path');
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testClientWssDefaultPort(): void
    {
        $uri = new Uri('wss://localhost');
        $uri = $uri->withPath('my/mock/path');

        $this->expectStreamFactory();
        $client = new Client($uri);
        $client->setStreamFactory(new StreamFactory());

        // Explicit connect and handshake
        $this->expectStreamFactoryCreateSockerClient()->addAssert(function ($method, $params) {
            $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
            $this->assertEquals('ssl://localhost:443', "{$params[0]}");
        });
        $this->expectSocketClient()->addAssert(function ($method, $params) {
            $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
            $this->assertEquals('ssl://localhost:443', "{$params[0]}");
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake('localhost:443', '/my/mock/path');
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testClientWithTimeout(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path', ['timeout' => 300]);
        $client->setStreamFactory(new StreamFactory());

        // Explicit connect and handshake
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
            $this->assertEquals(300, $params[0]);
        });
        $this->expectSocketClientSetContext();
        $this->expectSocketClientConnect();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(300, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake('localhost:8000', '/my/mock/path');
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testClientWithContext(): void
    {
        $context = stream_context_create(['ssl' => ['verify_peer' => false]]);

        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path', ['context' => $context]);
        $client->setStreamFactory(new StreamFactory());

        // Explicit connect and handshake
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
        $this->expectSocketClientSetContext()->addAssert(function ($method, $params) {
            $this->assertEquals(['ssl' => ['verify_peer' => false]], $params[0]);
        });
        $this->expectSocketClientConnect();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake('localhost:8000', '/my/mock/path');
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testClientAuthed(): void
    {
        $this->expectStreamFactory();
        $client = new Client('wss://usename:password@localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        // Explicit connect and handshake
        $this->expectStreamFactoryCreateSockerClient()->addAssert(function ($method, $params) {
            $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
            $this->assertEquals('ssl://localhost:8000', "{$params[0]}");
        });
        $this->expectSocketClient()->addAssert(function ($method, $params) {
            $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
            $this->assertEquals('ssl://localhost:8000', "{$params[0]}");
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake(
            'localhost:8000',
            '/my/mock/path',
            "authorization: Basic dXNlbmFtZTpwYXNzd29yZA==\r\n"
        );
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testWithHeaders(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path', [
            'origin' => 'Origin header',
            'headers' => ['Generic-header' => 'Generic content'],
        ]);
        $client->setStreamFactory(new StreamFactory());

        // Explicit connect and handshake
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake(
            'localhost:8000',
            '/my/mock/path',
            "origin: Origin header\r\nGeneric-header: Generic content\r\n"
        );
        $client->connect();

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

        // Explicit connect and handshake
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $payload = file_get_contents(__DIR__ . '/../../mock/payload.128.txt');

        // Sending message
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(136, strlen($params[0]));
        });
        $client->send($payload);

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

        $this->assertEquals($payload, $message);

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

        // Explicit connect and handshake
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $payload = file_get_contents(__DIR__ . '/../../mock/payload.65536.txt');

        // Sending message
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(65550, strlen($params[0]));
        });
        $client->send($payload);

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

        $this->assertEquals($payload, $message);
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

        // Explicit connect and handshake
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $client->setFragmentSize(8);

        // Sending message
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals('AQhNdWx0aSBmcg==', base64_encode($params[0]));
        })->setReturn(function () {
            return 14;
        });
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals('AAhhZ21lbnQgdA==', base64_encode($params[0]));
        })->setReturn(function () {
            return 14;
        });
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals('gANlc3Q=', base64_encode($params[0]));
        })->setReturn(function () {
            return 9;
        });
        $client->send('Multi fragment test', 'text', false);

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

        $this->assertEquals('Multi fragment test', $message);
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

        // Explicit connect and handshake
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        // Sending ping with content
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals('iQtTZXJ2ZXIgcGluZw==', base64_encode($params[0]));
        });
        $client->send('Server ping', 'ping', false);

        // Sending ping without content
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals('iQA=', base64_encode($params[0]));
        });
        $client->send('', 'ping', false);

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
        // Receiving pong for second ping
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
        // Receiving ping
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
        // Receiving text
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

        $this->assertEquals('Receiving a message', $message);
        $this->assertEquals('text', $client->getLastOpcode());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testRemoteClose(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        // Explicit connect and handshake
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
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
        // Sending close response
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamClose();
        $message = $client->receive();

        $this->expectSocketStreamIsConnected();
        unset($client);
    }

    public function testSetTimeout(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        // Explicit connect and handshake
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(300, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $client->setTimeout(300);

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testAutoConnect(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        // Implicit connect and handshake, send message
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake();
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamIsConnected();
        $client->send('Autoconnect');

        $this->expectSocketStreamIsConnected();
        $this->assertTrue($client->isConnected());
        $this->assertNull($client->getCloseStatus());

        // Close
        $this->expectSocketStreamWrite();
        // Receiving close response
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('iJo=');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('YvrScQ==');
        });
        $this->expectSocketStreamRead()->addAssert(function (string $method, array $params) {
            $this->assertEquals(26, $params[0]);
        })->setReturn(function () {
            return base64_decode('YRKRHQ2Jt1EDmbkfDY2+FAadtxVY2uNBUso=');
        });
        $this->expectSocketStreamClose();
        $client->close();

        $this->expectSocketStreamIsConnected();
        $this->assertFalse($client->isConnected());
        $this->assertEquals(1000, $client->getCloseStatus());
        $this->assertNull($client->getLastOpcode());

        // Implicit reconnect and handshake, receive message
        $this->expectSocketStreamIsConnected();
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
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

    public function testPersistentConnection(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path', ['persistent' => true]);
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
            $this->assertTrue($params[0]);
        });
        $this->expectSocketClientSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
        });
        $this->expectSocketClientSetContext();
        $this->expectSocketClientConnect();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamTell();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->disconnect();

        $this->expectSocketStreamIsConnected();
        unset($client);
    }

    public function testBadScheme(): void
    {
        $this->expectException('WebSocket\BadUriException');
        $this->expectExceptionMessage("Invalid URI scheme, must be 'ws' or 'wss'.");
        $client = new Client('bad://localhost:8000/my/mock/path');
    }

    public function testBadUri(): void
    {
        $this->expectException('WebSocket\BadUriException');
        $this->expectExceptionMessage("Invalid URI '--:this is not an uri:--' provided.");
        $client = new Client('--:this is not an uri:--');
    }

    public function testInvalidUriType(): void
    {
        $this->expectException('WebSocket\BadUriException');
        $this->expectExceptionMessage("Provided URI must be a UriInterface or string.");
        $client = new Client([]);
    }

    public function testBadStreamContext(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path', ['context' => 'BAD']);
        $client->setStreamFactory(new StreamFactory());

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Stream context in $options[\'context\'] isn\'t a valid context');
        $client->connect();

        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testFailedConnection(): void
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
        $this->expectException('WebSocket\ConnectionException');
        $this->expectExceptionCode(1100);
        $this->expectExceptionMessage('Could not open socket to "tcp://localhost:8000": Server is closed.');
        $client->connect();

        unset($client);
    }

    public function testHandshakeFailure(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        // Explicit connect and handshake
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamReadLine()->setReturn(function () {
            throw new StreamException(StreamException::FAIL_READ);
        });
        $this->expectException('WebSocket\ConnectionException');
        $this->expectExceptionCode(1101);
        $this->expectExceptionMessage('Client handshake error');
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->connect();

        unset($client);
    }

    public function testInvalidUpgrade(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        // Explicit connect and handshake
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nInvalid upgrade\r\n\r\n";
        });
        $this->expectException('WebSocket\ConnectionException');
        $this->expectExceptionCode(1101);
        $this->expectExceptionMessage('Connection to \'ws://localhost:8000/my/mock/path\' failed');
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->connect();

        unset($client);
    }

    public function testInvalidKey(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        // Explicit connect and handshake
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\n"
                . "Sec-WebSocket-Accept: BAD_KEY\r\n\r\n";
        });
        $this->expectException('WebSocket\ConnectionException');
        $this->expectExceptionCode(1101);
        $this->expectExceptionMessage('Server sent bad upgrade response');
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->connect();

        unset($client);
    }

    public function testSendBadOpcode(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        // Implicit connect and handshake, send message
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectException('WebSocket\BadOpcodeException');
        $this->expectExceptionMessage('Bad opcode \'bad\'.  Try \'text\' or \'binary\'.');
        $this->expectExceptionCode(1026);
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->send('Bad Opcode', 'bad');

        unset($client);
    }

    public function testRecieveBadOpcode(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        // Implicit connect and handshake, send message
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
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

        // Implicit connect and handshake, send message
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
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
        $this->expectException('WebSocket\ConnectionException');
        $this->expectExceptionCode(1025);
        $this->expectExceptionMessage('Could only write 18 out of 22 bytes.');
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $client->send('Failing to write');

        unset($client);
    }

    public function testReadTimeout(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        // Implicit connect and handshake, send message
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
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
        $this->expectException('WebSocket\TimeoutException');
        $this->expectExceptionCode(1024);
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

        // Implicit connect and handshake, send message
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
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
        $this->expectException('WebSocket\TimeoutException');
        $this->expectExceptionCode(1024);
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
        $client = new Client(
            'ws://localhost:8000/my/mock/path',
            ['filter' => ['text', 'binary', 'pong', 'close']]
        );
        $client->setStreamFactory(new StreamFactory());

        // Implicit connect and handshake, send message
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        // Receiving 2 frames for pong message
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
        $this->assertEquals('Server ping', $message);
        $this->assertEquals('pong', $client->getLastOpcode());

        // Receiving 2 frames for text message
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
        $this->assertEquals('Multi fragment test', $message);
        $this->assertEquals('text', $client->getLastOpcode());

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
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamClose();
        $message = $client->receive();

        $this->assertEquals('Closing', $message);
        $this->expectSocketStreamIsConnected();
        $this->assertFalse($client->isConnected());
        $this->assertEquals(17260, $client->getCloseStatus());
        $this->assertEquals('close', $client->getLastOpcode());

        $this->expectSocketStreamIsConnected();
        unset($client);
    }

    public function testMessageFragmentation(): void
    {
        $this->expectStreamFactory();
        $client = new Client(
            'ws://localhost:8000/my/mock/path',
            ['filter' => ['text', 'binary', 'pong', 'close'], 'return_obj' => true]
        );
        $client->setStreamFactory(new StreamFactory());

        // Implicit connect and handshake, send message
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        // Receiving 2 frames for pong message
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
        $this->assertInstanceOf('WebSocket\Message\Message', $message);
        $this->assertInstanceOf('WebSocket\Message\Pong', $message);
        $this->assertEquals('Server ping', $message->getContent());
        $this->assertEquals('pong', $message->getOpcode());

        // Receiving 2 frames for text message
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
        $this->assertInstanceOf('WebSocket\Message\Message', $message);
        $this->assertInstanceOf('WebSocket\Message\Text', $message);
        $this->assertEquals('Multi fragment test', $message->getContent());
        $this->assertEquals('text', $message->getOpcode());

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
        $this->expectSocketStreamWrite();
        $this->expectSocketStreamClose();
        $message = $client->receive();
        $this->assertInstanceOf('WebSocket\Message\Message', $message);
        $this->assertInstanceOf('WebSocket\Message\Close', $message);
        $this->assertEquals('Closing', $message->getContent());
        $this->assertEquals('close', $message->getOpcode());

        $this->expectSocketStreamIsConnected();
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

        // Implicit connect and handshake, send message
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
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        // Send "text"
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals('gQdDb25uZWN0', base64_encode($params[0]));
        });
        $client->text('Connect');

        // Send "binary"
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals('ghRRbWx1WVhKNUlHTnZiblJsYm5RPQ==', base64_encode($params[0]));
        });
        $client->binary(base64_encode('Binary content'));

        // Send "ping"
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals('iQA=', base64_encode($params[0]));
        });
        $client->ping();

        // Send "pong"
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals('igA=', base64_encode($params[0]));
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

    public function testUnconnectedClient(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path', ['masked' => false]);
        $client->setStreamFactory(new StreamFactory());

        $this->assertFalse($client->isConnected());
        $client->setTimeout(30);
        $client->close();
        $this->assertFalse($client->isConnected());
        $this->assertNull($client->getName());
        $this->assertNull($client->getRemoteName());
        $this->assertNull($client->getCloseStatus());
    }

    public function testDeprecated(): void
    {
        $client = new Client('ws://localhost:8000/my/mock/path');
        (new ErrorHandler())->withAll(function () use ($client) {
            $this->assertNull($client->getPier());
        }, function ($exceptions, $result) {
            $this->assertEquals(
                'getPier() is deprecated and will be removed in future version. Use getRemoteName() instead.',
                $exceptions[0]->getMessage()
            );
        }, E_USER_DEPRECATED);
    }
}
