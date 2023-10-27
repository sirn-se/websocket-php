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
    Ping,
    Message
};

/**
 * Test case for WebSocket\Message\Ping.
 */
class PingTest extends TestCase
{
    public function setUp(): void
    {
        error_reporting(-1);
    }

    public function testPingMessage(): void
    {
        $message = new Ping('Some content');
        $this->assertInstanceOf(Ping::class, $message);
        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals('Some content', $message->getContent());
        $this->assertEquals('ping', $message->getOpcode());
        $this->assertEquals(12, $message->getLength());
        $this->assertTrue($message->hasContent());
        $this->assertInstanceOf('DateTimeImmutable', $message->getTimestamp());
        $message->setContent('');
        $this->assertEquals(0, $message->getLength());
        $this->assertFalse($message->hasContent());
        $this->assertEquals('WebSocket\Message\Ping', "{$message}");

        $frames = $message->getFrames();
        $this->assertCount(1, $frames);
        $this->assertContainsOnlyInstancesOf(Frame::class, $frames);
    }

    public function testPingPayload(): void
    {
        $message = new Ping('Some content');
        $payload = $message->getPayload();
        $this->assertEquals('U29tZSBjb250ZW50', base64_encode($payload));
        $message = new Ping();
        $message->setPayload($payload);
        $this->assertEquals('Some content', $message->getContent());
    }
}
