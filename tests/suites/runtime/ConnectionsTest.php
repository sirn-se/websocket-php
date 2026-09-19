<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Runtime;

use Phrity\Net\Mock\SocketStream;
use PHPUnit\Framework\TestCase;
use WebSocket\{
    Configuration,
    Connection,
};
use WebSocket\Exception\RunnerException;
use WebSocket\Http\DefaultHttpFactory;
use WebSocket\Runtime\Connections;
use WebSocket\Test\MockStreamTrait;

/**
 * Test case for WebSocket\Runtime\Connections
 */
class ConnectionsTest extends TestCase
{
    use MockStreamTrait;

    public function setUp(): void
    {
        $this->setUpStack();
    }

    public function tearDown(): void
    {
        $this->tearDownStack();
    }

    public function testConnections(): void
    {
        $connections = new Connections(false, false, new DefaultHttpFactory(), new Configuration());
        $this->assertEquals(0, $connections->count());
        $this->assertTrue($connections->isEmpty());
        $this->assertFalse($connections->has('a'));
        $this->assertNull($connections->get('a'));
        $this->assertNull($connections->first());

        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = $connections->create($stream, false);
        $this->assertInstanceof(Connection::class, $connection);

        $identity = $connection->getIdentity();
        $connections->attach($connection);
        $this->assertEquals(1, $connections->count());
        $this->assertFalse($connections->isEmpty());
        $this->assertTrue($connections->has($identity));
        $this->assertSame($connection, $connections->get($identity));
        $this->assertSame($connection, $connections->first());

        foreach ($connections as $item) {
            $this->assertInstanceof(Connection::class, $item);
        }
        $this->assertEquals([$identity => $connection], $connections->toArray());

        $connections->walk(function (Connection $item) {
            $this->assertInstanceof(Connection::class, $item);
        });
        $filtered = $connections->filter(function (Connection $item) {
            return true;
        });
        $this->assertNotSame($connection, $filtered);

        $connections->detach($identity);
        $this->assertTrue($connections->isEmpty());

        $connections->attach($connection);
        $connections->reset();
        $this->assertTrue($connections->isEmpty());
    }

    public function testIdentityConflict(): void
    {
        $connections = new Connections(false, false, new DefaultHttpFactory(), new Configuration());

        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = $connections->create($stream, false);
        $this->assertInstanceof(Connection::class, $connection);

        $identity = $connection->getIdentity();
        $connections->attach($connection);

        $this->expectException(RunnerException::class);
        $this->expectExceptionMessage('Connection with identity */connection/<unknown>/<unknown> already attached');
        $connections->attach($connection);
    }
}
