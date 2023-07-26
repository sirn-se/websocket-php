<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

declare(strict_types=1);

namespace WebSocket\Test\Server;

use InvalidArgumentException;
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
class ConfigErrorTest extends TestCase
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

    public function testSchemaInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Invalid schema 'invalid' provided");
        $server = new Server(['schema' => 'invalid']);
    }

    public function testPortInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Invalid port 'invalid' provided");
        $server = new Server(['port' => 'invalid']);
    }

    public function testPortTooLow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Invalid port '-1' provided");
        $server = new Server(['port' => -1]);
    }

    public function testPortTooHigh(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Invalid port '65536' provided");
        $server = new Server(['port' => 65536]);
    }
}
