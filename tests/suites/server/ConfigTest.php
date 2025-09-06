<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Server;

use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\{
    Context,
    StreamFactory,
};
use Phrity\Net\Mock\Stack\{
    ExpectSocketServerTrait,
    ExpectSocketStreamTrait,
    ExpectStreamCollectionTrait,
    ExpectStreamFactoryTrait
};
use Psr\Log\NullLogger;
use WebSocket\{
    Connection,
    Server,
};
use WebSocket\Middleware\Callback;
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
        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->assertSame($server, $server->addMiddleware(new Callback()));
        $this->assertEquals('WebSocket\Server(closed)', "{$server}");
        $this->assertEquals(60, $server->getTimeout());
        $this->assertEquals(4096, $server->getFrameSize());
        $this->assertEquals(8000, $server->getPort());
        $this->assertEquals('tcp', $server->getScheme());
        $this->assertFalse($server->isRunning());
        $this->assertEquals(0, $server->getConnectionCount());
        $this->assertEmpty($server->getConnections());
        $this->assertEmpty($server->getReadableConnections());
        $this->assertEmpty($server->getWritableConnections());

        $this->expectWsServerSetup(scheme: 'tcp', port: 8000);
        $this->expectStreamCollectionCount();
        $this->expectStreamCollectionWaitRead()->addAssert(function ($method, $params) {
            $this->assertEquals(60, $params[0]);
        });
        $this->expectStreamCollection()->addAssert(function ($method, $params) use ($server) {
            $this->assertTrue($server->isRunning());
            $this->assertEquals(0, $server->getConnectionCount());
            $server->stop();
        });
        $server->start();

        $this->assertEquals('WebSocket\Server(tcp://0.0.0.0:8000)', "{$server}");
        $this->assertFalse($server->isRunning());

        $this->expectStreamCollectionDetach();
        $this->expectSocketServerClose();
        $server->disconnect();

        unset($server);
    }

    public function testServerConfiguration(): void
    {
        $this->expectWsServerCreate();
        $server = new Server(9000, true, streamFactory: new StreamFactory());

        $this->expectStreamFactory();
        $this->assertSame($server, $server->setStreamFactory(new StreamFactory()));

        $this->expectWsServerSetup(scheme: 'ssl', port: 9000);
        $this->expectWsSelectConnections(['@server']);
        // Accept connection
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $this->expectSocketStreamGetRemoteName()->setReturn(function () {
            return 'fake-connection-1';
        });
        $this->expectStreamCollectionAttach();
        $this->expectSocketStreamGetLocalName()->setReturn(function () {
            return 'fake-connection-1';
        });
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) use ($server) {
            $server->stop();
        });
        $this->expectWsServerPerformHandshake();
        $server->start();

        $server->setLogger(new NullLogger());
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) {
            $this->assertEquals(300, $params[0]);
        });
        $this->assertSame($server, $server->setTimeout(300));
        $this->assertSame($server, $server->setFrameSize(64));
        $this->assertSame($server, $server->setMaxConnections(null));
        $this->assertSame($server, $server->setMaxConnections(64));
        $this->assertSame($server, $server->addMiddleware(new Callback()));

        $this->assertEquals('WebSocket\Server(ssl://0.0.0.0:9000)', "{$server}");
        $this->assertEquals(300, $server->getTimeout());
        $this->assertEquals(64, $server->getFrameSize());
        $this->assertEquals(9000, $server->getPort());
        $this->assertEquals('ssl', $server->getScheme());
        $this->assertFalse($server->isRunning());
        $this->assertEquals(1, $server->getConnectionCount());
        $this->assertCount(1, $server->getConnections());
        $this->expectSocketStreamIsReadable();
        $this->assertCount(1, $server->getReadableConnections());
        $this->expectSocketStreamIsWritable();
        $this->assertCount(1, $server->getWritableConnections());

        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamClose();
        $this->expectSocketServerClose();
        $server->disconnect();

        unset($server);
    }

    public function testContext(): void
    {
        $this->expectContext();
        $context = new Context();
        $this->expectContextSetOptions();
        $this->expectContextSetOption();
        $context->setOptions(['ssl' => ['verify_peer' => false]]);

        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $server->onHandshake(function (Server $server, Connection $connection) {
            $this->expectSocketStreamGetContext();
            $connectionContext = $connection->getContext();
            // $connectionContext not populated in mock
            $this->expectContextGetOptions();
            $this->assertEmpty($connectionContext->getOptions());
            $server->stop();
        });

        $server->setContext($context);
        $this->assertSame($context, $server->getContext());

        $this->expectWsServerSetup(context: ['ssl' => ['verify_peer' => false]]);
        $this->expectWsSelectConnections(['@server']);
        // Accept connection
        $this->expectSocketServerAccept();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $this->expectSocketStreamGetRemoteName()->setReturn(function () {
            return 'fake-connection-1';
        });
        $this->expectStreamCollectionAttach();
        $this->expectSocketStreamGetLocalName()->setReturn(function () {
            return 'fake-connection-1';
        });
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $this->expectWsServerPerformHandshake();
        $server->start();

        $this->expectStreamCollectionDetach();
        $this->expectSocketStreamClose();
        $this->expectSocketServerClose();
        $server->disconnect();

        unset($server);
    }

    public function testHttpFactories(): void
    {
        $httpFactory = new HttpFactory();
        $this->expectContext();
        $context = new Context();
        $this->expectContextSetOptions();
        $this->expectContextSetOption();
        $context->setOptions(['ssl' => ['verify_peer' => false]]);

        $this->expectWsServerCreate();
        $server = new Server(8000, streamFactory: new StreamFactory());

        $this->assertSame($server, $server->setResponseFactory($httpFactory));
        $this->assertSame($server, $server->setServerRequestFactory($httpFactory));
        $this->assertSame($server, $server->setUriFactory($httpFactory));

        $this->expectStreamCollectionDetach();
        $server->disconnect();

        unset($server);
    }
}
