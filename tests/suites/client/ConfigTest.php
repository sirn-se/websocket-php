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
use Phrity\Net\Uri;
use WebSocket\Client;
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

    public function testUriStringExtended(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path?my_query=yes#my_fragment');
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake('localhost:8000', '/my/mock/path?my_query=yes');
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testUriStringWithoutPath(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000');
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake('localhost:8000', '/');
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testUriInstanceRelativePath(): void
    {
        $uri = new Uri('ws://localhost:8000');
        $uri = $uri->withPath('my/mock/path');

        $this->expectStreamFactory();
        $client = new Client($uri);
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake('localhost:8000', '/my/mock/path');
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testUriInstanceWsDefaultPort(): void
    {
        $uri = new Uri('ws://localhost');
        $uri = $uri->withPath('my/mock/path');

        $this->expectStreamFactory();
        $client = new Client($uri);
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect(port: 80);
        $this->expectWsClientPerformHandshake('localhost:80', '/my/mock/path');
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testUriInstanceWssDefaultPort(): void
    {
        $uri = new Uri('wss://localhost');
        $uri = $uri->withPath('my/mock/path');

        $this->expectStreamFactory();
        $client = new Client($uri);
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect(scheme: 'ssl', port: 443);
        $this->expectWsClientPerformHandshake('localhost:443', '/my/mock/path');
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testUriStringAuthorization(): void
    {
        $this->expectStreamFactory();
        $client = new Client('wss://usename:password@localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect(scheme: 'ssl');
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

    public function testUriInstanceImplementation(): void
    {
        $uri = new MockUri();

        $this->expectStreamFactory();
        $client = new Client($uri);
        $client->setStreamFactory(new StreamFactory());

        unset($client);
    }

    public function testTimeoutOption(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path', ['timeout' => 300]);
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect(timeout: 300);
        $this->expectWsClientPerformHandshake('localhost:8000', '/my/mock/path');
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testContextOption(): void
    {
        $context = stream_context_create(['ssl' => ['verify_peer' => false]]);

        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path', ['context' => $context]);
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect(context: ['ssl' => ['verify_peer' => false]]);
        $this->expectWsClientPerformHandshake('localhost:8000', '/my/mock/path');
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testContextArrayOption(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path', ['context' => ['ssl' => ['verify_peer' => false]]]);
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect(context: ['ssl' => ['verify_peer' => false]]);
        $this->expectWsClientPerformHandshake('localhost:8000', '/my/mock/path');
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testHeadersOption(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path', [
            'headers' => ['Generic-header' => 'Generic content'],
        ]);
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect();
        $this->expectWsClientPerformHandshake(
            'localhost:8000',
            '/my/mock/path',
            "Generic-header: Generic content\r\n"
        );
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testPersistentOption(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path', ['persistent' => true]);
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect(persistent: true);
        $this->expectWsClientPerformHandshake();
        $client->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($client);
    }

    public function testSetTimeout(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new StreamFactory());

        $this->expectWsClientConnect();
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
}
