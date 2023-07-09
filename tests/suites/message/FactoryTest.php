<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

declare(strict_types=1);

namespace WebSocket\Test\Message;

use PHPUnit\Framework\TestCase;
use WebSocket\BadOpcodeException;
use WebSocket\Message\{
    Binary,
    Close,
    Factory,
    Ping,
    Pong,
    Text,
};

/**
 * Test case for WebSocket\Message\Factory.
 */
class FactoryTest extends TestCase
{
    public function setUp(): void
    {
        error_reporting(-1);
    }

    public function testFactory(): void
    {
        $factory = new Factory();
        $this->assertInstanceOf(Factory::class, $factory);

        $message = $factory->create('binary', 'Some content');
        $this->assertInstanceOf(Binary::class, $message);
        $message = $factory->create('binary');
        $this->assertInstanceOf(Binary::class, $message);

        $message = $factory->create('close', 'Some content');
        $this->assertInstanceOf(Close::class, $message);
        $message = $factory->create('close');
        $this->assertInstanceOf(Close::class, $message);

        $message = $factory->create('ping', 'Some content');
        $this->assertInstanceOf(Ping::class, $message);
        $message = $factory->create('ping');
        $this->assertInstanceOf(Ping::class, $message);

        $message = $factory->create('pong', 'Some content');
        $this->assertInstanceOf(Pong::class, $message);
        $message = $factory->create('pong');
        $this->assertInstanceOf(Pong::class, $message);

        $message = $factory->create('text', 'Some content');
        $this->assertInstanceOf(Text::class, $message);
        $message = $factory->create('text');
        $this->assertInstanceOf(Text::class, $message);
    }

    public function testBadOpcodeError(): void
    {
        $factory = new Factory();

        $this->expectException(BadOpcodeException::class);
        $this->expectExceptionCode(BadOpcodeException::BAD_OPCODE);
        $this->expectExceptionMessage("Invalid opcode 'invalid' provided");
        $message = $factory->create('invalid', 'Some content');
    }
}
