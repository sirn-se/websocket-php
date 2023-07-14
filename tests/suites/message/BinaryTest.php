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
use WebSocket\Frame\Frame;
use WebSocket\Message\{
    Binary,
    Message
};

/**
 * Test case for WebSocket\Message\Binary.
 */
class BinaryTest extends TestCase
{
    public function setUp(): void
    {
        error_reporting(-1);
    }

    public function testBinaryMessage(): void
    {
        $bin = base64_encode('Some content');
        $message = new Binary($bin);
        $this->assertInstanceOf(Binary::class, $message);
        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals($bin, $message->getContent());
        $this->assertEquals('binary', $message->getOpcode());
        $this->assertEquals(16, $message->getLength());
        $this->assertTrue($message->hasContent());
        $this->assertInstanceOf('DateTime', $message->getTimestamp());
        $message->setContent('');
        $this->assertEquals(0, $message->getLength());
        $this->assertFalse($message->hasContent());
        $this->assertEquals('WebSocket\Message\Binary', "{$message}");

        $frames = $message->getFrames();
        $this->assertCount(1, $frames);
        $this->assertContainsOnlyInstancesOf(Frame::class, $frames);
    }
}
