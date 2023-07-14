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
    Text,
    Message
};

/**
 * Test case for WebSocket\Message\Text.
 */
class TextTest extends TestCase
{
    public function setUp(): void
    {
        error_reporting(-1);
    }

    public function testTextMessage(): void
    {
        $message = new Text('Some content');
        $this->assertInstanceOf(Text::class, $message);
        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals('Some content', $message->getContent());
        $this->assertEquals('text', $message->getOpcode());
        $this->assertEquals(12, $message->getLength());
        $this->assertTrue($message->hasContent());
        $this->assertInstanceOf('DateTime', $message->getTimestamp());
        $message->setContent('');
        $this->assertEquals(0, $message->getLength());
        $this->assertFalse($message->hasContent());
        $this->assertEquals('WebSocket\Message\Text', "{$message}");

        $frames = $message->getFrames();
        $this->assertCount(1, $frames);
        $this->assertContainsOnlyInstancesOf(Frame::class, $frames);
    }

    public function testTextPayload(): void
    {
        $message = new Text('Some content');
        $payload = $message->getPayload();
        $this->assertEquals('U29tZSBjb250ZW50', base64_encode($payload));
        $message = new Text();
        $message->setPayload($payload);
        $this->assertEquals('Some content', $message->getContent());
    }
}
