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
    ExpectStreamCollectionTrait,
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

    public function testServerDefaults(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new StreamFactory());

        $this->assertEquals(60, $server->getTimeout());
        $this->assertEquals(4096, $server->getFrameSize());
        $this->assertEquals(8000, $server->getPort());
        $this->assertEquals('tcp', $server->getScheme());
        $this->assertFalse($server->isRunning());
        $this->assertEquals(0, $server->getConnectionCount());

        $this->expectWsServerAccept(schema: 'tcp', port: 8000);
        $this->expectStreamFactoryCreateStreamCollection();
        $this->expectStreamCollection();
        $this->expectStreamCollectionAttach();
        $this->expectStreamCollectionWaitRead()->addAssert(function ($method, $params) {
            $this->assertEquals(60, $params[0]);
        });
        $this->expectStreamCollection();
        $this->expectStreamCollectionRewind();
        $this->expectStreamCollectionValid()->addAssert(function ($method, $params) use ($server) {
            $this->assertTrue($server->isRunning());
            $this->assertEquals(0, $server->getConnectionCount());
            $server->stop();
        });
        $server->start();
        $this->assertFalse($server->isRunning());

        unset($server);
    }

    public function testServerConfiguration(): void
    {
        $this->expectStreamFactory();
        $server = new Server(9000, 'ssl');
        $server->setStreamFactory(new StreamFactory());

        $server->setLogger(new NullLogger());
        $server->setTimeout(300);
        $server->setFrameSize(64);

        $this->assertEquals(300, $server->getTimeout());
        $this->assertEquals(64, $server->getFrameSize());
        $this->assertEquals(9000, $server->getPort());
        $this->assertEquals('ssl', $server->getScheme());
        $this->assertFalse($server->isRunning());
        $this->assertEquals(0, $server->getConnectionCount());

        $this->expectWsServerAccept(schema: 'ssl', port: 9000);
        $this->expectStreamFactoryCreateStreamCollection();
        $this->expectStreamCollection();
        $this->expectStreamCollectionAttach();
        $this->expectStreamCollectionWaitRead()->addAssert(function ($method, $params) {
            $this->assertEquals(300, $params[0]);
        });
        $this->expectStreamCollection();
        $this->expectStreamCollectionRewind();
        $this->expectStreamCollectionValid()->addAssert(function ($method, $params) use ($server) {
            $this->assertTrue($server->isRunning());
            $this->assertEquals(0, $server->getConnectionCount());
            $server->stop();
        });
        $server->start();
        $this->assertFalse($server->isRunning());

        unset($server);
    }
}
