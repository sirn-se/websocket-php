<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Client;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Phrity\Http\HttpFactory;
use Phrity\Net\Mock\{
    Context,
    StreamFactory,
};
use Phrity\Net\Mock\Stack\{
    ExpectSocketClientTrait,
    ExpectSocketStreamTrait,
    ExpectStreamCollectionTrait,
    ExpectStreamFactoryTrait
};
use Phrity\Net\Uri;
use Phrity\Util\ErrorHandler;
use Psr\Log\NullLogger;
use WebSocket\{
    Client,
    Configuration,
    Connection,
};
use WebSocket\Middleware\CloseHandler;
use WebSocket\Test\{
    MockStreamTrait,
    MockUri
};

/**
 * Test case for WebSocket\Client: Setup & configuration.
 */
class ConfigTest extends TestCase
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

    public function testUriStringExtended(): void
    {
        $this->expectWsClientCreate();
        $client = new Client(
            'ws://localhost:8000/my/mock/path?my_query=yes#my_fragment',
            streamFactory: new StreamFactory()
        );

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake('localhost:8000', '/my/mock/path?my_query=yes');
        $client->connect();

        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->disconnect();
    }

    public function testUriStringWithoutPath(): void
    {
        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000', streamFactory: new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake('localhost:8000', '/');
        $client->connect();

        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->disconnect();
    }

    public function testUriInstanceRelativePath(): void
    {
        $uri = new Uri('ws://localhost:8000');
        $uri = $uri->withPath('my/mock/path');

        $this->expectWsClientCreate();
        $client = new Client($uri, streamFactory: new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake('localhost:8000', '/my/mock/path');
        $client->connect();

        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->disconnect();
    }

    public function testUriInstanceWsDefaultPort(): void
    {
        $uri = new Uri('ws://localhost');
        $uri = $uri->withPath('my/mock/path');

        $this->expectWsClientCreate();
        $client = new Client($uri, streamFactory: new StreamFactory());

        $this->expectWsClientConnect(port: 80);
        $this->expectWsClientPerformHandshake('localhost', '/my/mock/path');
        $client->connect();

        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->disconnect();
    }

    public function testUriInstanceWssDefaultPort(): void
    {
        $uri = new Uri('wss://localhost');
        $uri = $uri->withPath('my/mock/path');

        $this->expectWsClientCreate();
        $client = new Client($uri, streamFactory: new StreamFactory());

        $this->expectWsClientConnect(scheme: 'ssl', port: 443);
        $this->expectWsClientPerformHandshake('localhost', '/my/mock/path');
        $client->connect();

        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->disconnect();
    }

    /** @return array<mixed> */
    public static function uriStringAuthorizationDataProvider(): array
    {
        $encoded = urlencode('7{v^pF8;uPK.6VWu');
        return [
            [
                'usename:password',
                'dXNlbmFtZTpwYXNzd29yZA==',
            ],
            [
                'usename',
                'dXNlbmFtZQ==',
            ],
            [
                "{$encoded}:{$encoded}",
                'N3t2XnBGODt1UEsuNlZXdTo3e3ZecEY4O3VQSy42Vld1',
            ],
        ];
    }

    #[DataProvider('uriStringAuthorizationDataProvider')]
    public function testUriStringAuthorization(string $uriAuth, string $expectedCredentials): void
    {
        $this->expectWsClientCreate();
        $client = new Client("wss://{$uriAuth}@localhost:8000/my/mock/path", streamFactory: new StreamFactory());

        $this->expectWsClientConnect(scheme: 'ssl');
        $this->expectWsClientPerformHandshake(
            'localhost:8000',
            '/my/mock/path',
            "Authorization: Basic {$expectedCredentials}\r\n"
        );
        $client->connect();

        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->disconnect();
    }

    public function testUriInstanceImplementation(): void
    {
        $uri = new MockUri();

        $this->expectWsClientCreate();
        $client = new Client($uri, streamFactory: new StreamFactory());
    }

    public function testTimeoutOption(): void
    {
        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000/my/mock/path', streamFactory: new StreamFactory());
        $client->setTimeout(300);

        $this->expectWsClientConnect(timeout: 300);
        $this->expectWsClientPerformHandshake('localhost:8000', '/my/mock/path');
        $client->connect();

        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->disconnect();
    }

    public function testContextOption(): void
    {
        $errorHandler = new ErrorHandler();
        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000/my/mock/path', streamFactory: new StreamFactory());

        $errorHandler->withAll(function () use ($client) {
            $client->setContext(['ssl' => ['verify_peer' => false]]);
        }, function (array $errors) {
            $this->assertEquals(
                'Calling Client.setContext with array is deprecated, use Context class.',
                $errors[0]->getMessage()
            );
        });

        $this->expectWsClientConnect(context: ['ssl' => ['verify_peer' => false]]);
        $this->expectWsClientPerformHandshake('localhost:8000', '/my/mock/path');
        $client->connect();

        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->disconnect();
    }

    public function testContextClass(): void
    {
        $this->expectContext();
        $context = new Context();
        $this->expectContextSetOptions();
        $this->expectContextSetOption();
        $context->setOptions(['ssl' => ['verify_peer' => false]]);

        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000/my/mock/path', streamFactory: new StreamFactory());
        $client->onHandshake(function (Client $client, Connection $connection) {
            $this->expectSocketStreamGetContext();
            $connectionContext = $connection->getContext();
            // $connectionContext not populated in mock
            $this->expectContextGetOptions();
            $this->assertEmpty($connectionContext->getOptions());
        });

        $client->setContext($context);
        $this->assertSame($context, $client->getContext());

        $this->expectWsClientConnect(context: ['ssl' => ['verify_peer' => false]]);
        $this->expectWsClientPerformHandshake('localhost:8000', '/my/mock/path');
        $client->connect();

        $this->assertSame($context, $client->getContext());

        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->disconnect();
    }

    public function testHeadersOption(): void
    {
        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000/my/mock/path', streamFactory: new StreamFactory());
        $client->addHeader('Generic-header', 'Generic content');

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake(
            'localhost:8000',
            '/my/mock/path',
            "Generic-header: Generic content\r\n"
        );
        $client->connect();

        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->disconnect();
    }

    public function testPersistentOption(): void
    {
        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000/my/mock/path', streamFactory: new StreamFactory());
        $client->setPersistent(true);

        $this->expectWsClientConnect(persistent: true);
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->disconnect();
    }

    public function testConfigUnconnectedClient(): void
    {
        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000/my/mock/path', streamFactory: new StreamFactory());

        $this->assertFalse($client->isConnected());
        $client->setLogger(new NullLogger());
        $client->setTimeout(300);
        $this->assertEquals(300, $client->getTimeout());
        $client->setFrameSize(64);
        $this->assertEquals(64, $client->getFrameSize());
    }

    public function testConfigConnectedClient(): void
    {
        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000/my/mock/path', streamFactory: new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $client->setLogger(new NullLogger());
        $client->addMiddleware(new CloseHandler());

        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(300, $params[0]);
        });
        $client->setTimeout(300);
        $client->setFrameSize(64);
        $this->assertEquals(64, $client->getFrameSize());

        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $client->disconnect();
    }

    public function testHttpFactories(): void
    {
        $httpFactory = new Psr17Factory();
        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000/my/mock/path', streamFactory: new StreamFactory());

        $this->assertSame($client, $client->setHttpFactory(HttpFactory::create($httpFactory)));
    }

    public function testConfiguration(): void
    {
        $logger = new NullLogger();
        $this->expectContext();
        $context = new Context();
        $configuration = new Configuration(
            logger: $logger,
            context: $context,
            timeout: 120,
            frameSize: 64,
            persistent: true,
        );
        $this->expectWsClientCreate();
        $client = new Client('ws://localhost:8000/my/mock/path', streamFactory: new StreamFactory());
        $this->assertInstanceOf(Configuration::class, $client->getConfiguration());
        $this->assertSame($client, $client->setConfiguration($configuration));
    }
}
