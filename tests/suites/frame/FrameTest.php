<?php

/**
 * Test case for Frame.
 */

declare(strict_types=1);

namespace WebSocket;

use PHPUnit\Framework\TestCase;
use WebSocket\BadOpcodeException;
use WebSocket\Frame\Frame;

class FrameTest extends TestCase
{
    public function setUp(): void
    {
        error_reporting(-1);
    }

    public function testTextFrame(): void
    {
        $frame = new Frame('text', 'Text message', true);
        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertTrue($frame->isFinal());
        $this->assertFalse($frame->isContinuation());
        $this->assertEquals('text', $frame->getOpcode());
        $this->assertEquals('Text message', $frame->getPayload());
        $this->assertEquals(12, $frame->getPayloadLength());
    }

    public function testContinuationFrame(): void
    {
        $frame = new Frame('continuation', '0123456789', false);
        $this->assertInstanceOf(Frame::class, $frame);
        $this->assertFalse($frame->isFinal());
        $this->assertTrue($frame->isContinuation());
        $this->assertEquals('continuation', $frame->getOpcode());
        $this->assertEquals('0123456789', $frame->getPayload());
        $this->assertEquals(10, $frame->getPayloadLength());
    }

    public function testBadOpcode(): void
    {
        $this->expectException(BadOpcodeException::class);
        $this->expectExceptionCode(BadOpcodeException::BAD_OPCODE);
        $this->expectExceptionMessage("Invalid opcode 'invalid' provided");
        $frame = new Frame('invalid', '0123456789', true);
    }
}
