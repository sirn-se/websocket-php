<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Message;

use DomainException;
use PHPUnit\Framework\TestCase;
use RangeException;
use WebSocket\Exception\BadOpcodeException;
use WebSocket\Message\{
    Message,
    Binary,
    Close,
    OpcodeRegistry,
    Ping,
    Pong,
    Text,
};
use WebSocket\Test\MockOpcodeRegistry;

/**
 * Test case for WebSocket\Message\OpcodeRegistry.
 */
class OpcodeRegistryTest extends TestCase
{
    public function testOpcodeRegistry(): void
    {
        $opcodeRegistry = new OpcodeRegistry();

        $this->assertEquals(1, $opcodeRegistry->getOpcode(Text::class));
        $this->assertEquals(2, $opcodeRegistry->getOpcode(Binary::class));
        $this->assertEquals(8, $opcodeRegistry->getOpcode(Close::class));
        $this->assertEquals(9, $opcodeRegistry->getOpcode(Ping::class));
        $this->assertEquals(10, $opcodeRegistry->getOpcode(Pong::class));
        $this->assertInstanceOf(Text::class, $opcodeRegistry->createMessage(1));
        $this->assertInstanceOf(Binary::class, $opcodeRegistry->createMessage(2));
        $this->assertInstanceOf(Close::class, $opcodeRegistry->createMessage(8));
        $this->assertInstanceOf(Ping::class, $opcodeRegistry->createMessage(9));
        $this->assertInstanceOf(Pong::class, $opcodeRegistry->createMessage(10));

        $opcodeRegistry->register(2, Text::class);
        $opcodeRegistry->register(3, Text::class);
        $this->assertInstanceOf(Text::class, $opcodeRegistry->createMessage(2));
        $this->assertInstanceOf(Text::class, $opcodeRegistry->createMessage(3));
    }

    public function testRegisterInvalidOpcode(): void
    {
        $opcodeRegistry = new OpcodeRegistry();
        $this->expectException(RangeException::class);
        $this->expectExceptionMessage('Opcode must be integer in range 1-15, 16 provided');
        // @phpstan-ignore argument.type
        $opcodeRegistry->register(16, Text::class);
    }

    public function testRegisterUnexistingClass(): void
    {
        $opcodeRegistry = new OpcodeRegistry();
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Implementation class "UnexistingClass" not found');
        // @phpstan-ignore argument.type
        $opcodeRegistry->register(1, 'UnexistingClass');
    }

    public function testRegisterInvalidClass(): void
    {
        $opcodeRegistry = new OpcodeRegistry();
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'Implementation class WebSocket\Message\OpcodeRegistry must extend WebSocket\Message\Message'
        );
        $opcodeRegistry->register(1, $opcodeRegistry::class);
    }

    public function testGetOpcodeInvalidOpcode(): void
    {
        $opcodeRegistry = new OpcodeRegistry();
        $this->expectException(BadOpcodeException::class);
        $this->expectExceptionMessage('Opcode must be integer in range 1-15, false provided');
        $opcodeRegistry->getOpcode($opcodeRegistry::class);
    }

    public function testCreateMessageInvalidOpcode(): void
    {
        $opcodeRegistry = new OpcodeRegistry();
        $this->expectException(BadOpcodeException::class);
        $this->expectExceptionMessage('Opcode must be integer in range 1-15, 16 provided');
        // @phpstan-ignore argument.type
        $opcodeRegistry->createMessage(16);
    }

    public function testCreateMessageUnexistingClass(): void
    {
        $opcodeRegistry = new MockOpcodeRegistry();
        $opcodeRegistry->mockBind(1, 'UnexistingClass');
        $this->expectException(BadOpcodeException::class);
        $this->expectExceptionMessage('Implementation class "UnexistingClass" for opcode 1 not found');
        $opcodeRegistry->createMessage(1);
    }

    public function testCreateMessageInvalidClass(): void
    {
        $opcodeRegistry = new MockOpcodeRegistry();
        $opcodeRegistry->mockBind(2, $opcodeRegistry::class);
        $this->expectException(BadOpcodeException::class);
        $this->expectExceptionMessage(
            'Implementation class WebSocket\Test\MockOpcodeRegistry must extend WebSocket\Message\Message'
        );
        $opcodeRegistry->createMessage(2);
    }
}
