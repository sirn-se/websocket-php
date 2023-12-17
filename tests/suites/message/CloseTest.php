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
use Stringable;
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
        $message = new Close(1000, 'Some content');
        $this->assertInstanceOf(Close::class, $message);
        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals('Some content', $message->getContent());
        $this->assertEquals('close', $message->getOpcode());
        $this->assertEquals(1000, $message->getCloseStatus());
        $this->assertEquals(12, $message->getLength());
        $this->assertTrue($message->hasContent());
        $this->assertInstanceOf('DateTimeImmutable', $message->getTimestamp());
        $message->setContent('');
        $message->setCloseStatus(1020);
        $this->assertEquals(0, $message->getLength());
        $this->assertFalse($message->hasContent());
        $this->assertEquals(1020, $message->getCloseStatus());
        $this->assertInstanceOf(Stringable::class, $message);
        $this->assertEquals('WebSocket\Message\Close', "{$message}");

        $frames = $message->getFrames();
        $this->assertCount(1, $frames);
        $this->assertContainsOnlyInstancesOf(Frame::class, $frames);
    }

    public function testClosePayload(): void
    {
        $message = new Close(1000, 'Some content');
        $payload = $message->getPayload();
        $this->assertEquals('A+hTb21lIGNvbnRlbnQ=', base64_encode($payload));
        $message = new Close();
        $message->setPayload($payload);
        $this->assertEquals(1000, $message->getCloseStatus());
        $this->assertEquals('Some content', $message->getContent());
    }
}
