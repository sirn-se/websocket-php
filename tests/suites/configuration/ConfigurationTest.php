<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Configuration;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Phrity\Logger\Console\ConsoleLogger;
use Phrity\Net\Context;
use Psr\Log\{
    LoggerInterface,
    NullLogger,
};
use Stringable;
use WebSocket\Configuration;

/**
 * Test case for WebSocketC\onfiguration.
 */
class ConfigurationTest extends TestCase
{
    public function testDefaults(): void
    {
        $configuration = new Configuration();
        $this->assertInstanceOf(NullLogger::class, $configuration->getLogger());
        $this->assertInstanceOf(Context::class, $configuration->getContext());
        $this->assertEquals(60, $configuration->getTimeout());
        $this->assertEquals(4096, $configuration->getFrameSize());
        $this->assertFalse($configuration->isPersistent());
        $this->assertEquals(null, $configuration->getMaxConnections());
        $this->assertEquals('WebSocket\Configuration()', "{$configuration}");
    }

    public function testConstructor(): void
    {
        $configuration = new Configuration(
            logger: new ConsoleLogger(),
            context: new Context(),
            timeout: 360,
            frameSize: 65540,
            persistent: true,
            maxConnections: 10,
        );
        $this->assertInstanceOf(ConsoleLogger::class, $configuration->getLogger());
        $this->assertInstanceOf(Context::class, $configuration->getContext());
        $this->assertEquals(360, $configuration->getTimeout());
        $this->assertEquals(65540, $configuration->getFrameSize());
        $this->assertTrue($configuration->isPersistent());
        $this->assertEquals(10, $configuration->getMaxConnections());
    }

    public function testSetters(): void
    {
        $configuration = new Configuration();
        $configuration->setLogger(new ConsoleLogger());
        $configuration->setContext(new Context());
        $configuration->setTimeout(360);
        $configuration->setFrameSize(65540);
        $configuration->setPersistent(true);
        $configuration->setMaxConnections(10);
        $this->assertInstanceOf(ConsoleLogger::class, $configuration->getLogger());
        $this->assertInstanceOf(Context::class, $configuration->getContext());
        $this->assertEquals(360, $configuration->getTimeout());
        $this->assertEquals(65540, $configuration->getFrameSize());
        $this->assertTrue($configuration->isPersistent());
        $this->assertEquals(10, $configuration->getMaxConnections());
    }

    public function testInvalidTimeout(): void
    {
        $configuration = new Configuration();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Invalid timeout '-1' provided");
        $configuration->setTimeout(-1);
    }

    public function testInvalidFrameSize(): void
    {
        $configuration = new Configuration();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Invalid frameSize '0' provided");
        // @phpstan-ignore argument.type
        $configuration->setFrameSize(0);
    }

    public function testInvalidMaxConnextions(): void
    {
        $configuration = new Configuration();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Invalid maxConnections '0' provided");
        // @phpstan-ignore argument.type
        $configuration->setMaxConnections(0);
    }
}
