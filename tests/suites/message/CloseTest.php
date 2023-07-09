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
    Close,
    Message
};

/**
 * Test case for WebSocket\Message\Close.
 */
class CloseTest extends TestCase
{
    public function setUp(): void
    {
        error_reporting(-1);
    }

    public function testCloseMessage(): void
    {
        $message = new Close('Some content');
        $this->assertInstanceOf(Close::class, $message);
        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals('Some content', $message->getContent());
        $this->assertEquals('close', $message->getOpcode());
        $this->assertEquals(12, $message->getLength());
        $this->assertTrue($message->hasContent());
        $this->assertInstanceOf('DateTime', $message->getTimestamp());
        $message->setContent('');
        $this->assertEquals(0, $message->getLength());
        $this->assertFalse($message->hasContent());
        $this->assertEquals('WebSocket\Message\Close', "{$message}");

        $frames = $message->getFrames();
        $this->assertCount(1, $frames);
        $this->assertContainsOnlyInstancesOf(Frame::class, $frames);
    }
}
