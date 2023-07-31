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
use WebSocket\Server;

/**
 * Test case for WebSocket\Server: Setup & configuration errors.
 */
class ConfigErrorTest extends TestCase
{
    public function setUp(): void
    {
        error_reporting(-1);
    }

    public function testSchemeInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Invalid scheme 'invalid' provided");
        $server = new Server(8000, 'invalid');
    }

    public function testPortTooLow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Invalid port '-1' provided");
        $server = new Server(-1);
    }

    public function testPortTooHigh(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Invalid port '65536' provided");
        $server = new Server(65536);
    }

    public function testInvalidTimeout(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Invalid timeout '-1' provided");
        $server = new Server();
        $server->setTimeout(-1);
    }

    public function testInvalidFrameSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Invalid frameSize '0' provided");
        $server = new Server();
        $server->setFrameSize(0);
    }
}
