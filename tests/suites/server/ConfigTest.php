<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

declare(strict_types=1);

namespace WebSocket\Test\Server;

use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\StreamFactory;
use Phrity\Net\Mock\Stack\{
    ExpectSocketServerTrait,
    ExpectSocketStreamTrait,
    ExpectStreamFactoryTrait
};
use Psr\Log\NullLogger;
use WebSocket\Server;
use WebSocket\Test\{
    MockStreamTrait,
    MockUri
};

/**
 * Test case for WebSocket\Server: Setup & configuration.
 */
class ConfigTest extends TestCase
{
    use ExpectSocketServerTrait;
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

    public function testServerDefaults(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerAccept(schema: 'tcp', port: 8000);
        $this->expectStreamFactoryCreateStreamCollection();
        $server->start();

        $this->expectWsServerConnect(timeout: null);
        $this->expectWsServerPerformHandshake();
        $server->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($server);
    }

    public function xxxtestServerOptions(): void
    {
        $this->expectStreamFactory();
        $server = new Server([
            'fragment_size' => 1000,
            'logger' => new NullLogger(),
            'timeout' => 300,
            'schema' => 'ssl',
            'port' => 8001,
        ]);
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerAccept(schema: 'ssl', port: 8001);
        $server->accept();

        $this->expectWsServerConnect(timeout: 300);
        $this->expectWsServerPerformHandshake();
        $server->connect();

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($server);
    }

    public function xxxtestConfigUnconnectedServer(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->assertFalse($server->isConnected());
        $server->setLogger(new NullLogger());
        $server->setTimeout(300);
        $server->setFragmentSize(64);
        $this->assertEquals(64, $server->getFragmentSize());
    }


    public function xxxtestConfigConnectedClient(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->expectWsServerAccept();
        $server->accept();

        $this->expectWsServerConnect(timeout: null);
        $this->expectWsServerPerformHandshake();
        $server->connect();

        $server->setLogger(new NullLogger());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(300, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $server->setTimeout(300);

        $server->setFragmentSize(64);
        $this->assertEquals(64, $server->getFragmentSize());

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        unset($server);
    }
}
