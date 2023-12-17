<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

declare(strict_types=1);

namespace WebSocket\Test\Frame;

use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\SocketStream;
use Phrity\Net\Mock\Stack\ExpectSocketStreamTrait;
use RuntimeException;
use Stringable;
use WebSocket\ConnectionException;
use WebSocket\Frame\{
    Frame,
    FrameHandler
};

/**
 * Test case for WebSocket\Frame\FrameHandler.
 */
class FrameHandlerTest extends TestCase
{
    use ExpectSocketStreamTrait;

    public function setUp(): void
    {
        error_reporting(-1);
        $this->setUpStack();
    }

    public function tearDown(): void
    {
        $this->tearDownStack();
    }

    public function testPushUnmaskedFrame(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $handler = new FrameHandler($stream, false, false);
        $this->assertInstanceOf(FrameHandler::class, $handler);
        $this->assertInstanceOf(Stringable::class, $handler);

        $frame = new Frame('text', 'Text message', true);
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(14, strlen($params[0]));
            $this->assertEquals(base64_decode('gQxUZXh0IG1lc3NhZ2U='), $params[0]);
            $this->assertEquals('Text message', substr($params[0], 2));
        });
        $written = $handler->push($frame, false);
        $this->assertEquals(14, $written);
        $this->assertEquals('WebSocket\Frame\FrameHandler', "{$handler}");

        fclose($temp);
    }

    public function testPullUnmaskedFrame(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $handler = new FrameHandler($stream, false, false);
        $this->assertInstanceOf(FrameHandler::class, $handler);

        $this->expectSocketStreamRead()->addAssert(function ($method, $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('gQw=');
        });
        $this->expectSocketStreamRead()->addAssert(function ($method, $params) {
            $this->assertEquals(12, $params[0]);
        })->setReturn(function () {
            return 'Text message';
        });
        $frame = $handler->pull();
        $this->assertEquals('Text message', $frame->getPayload());
        $this->assertEquals('text', $frame->getOpcode());

        fclose($temp);
    }

    public function testPushMaskedFrame(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $handler = new FrameHandler($stream, true, false);
        $this->assertInstanceOf(FrameHandler::class, $handler);

        $frame = new Frame('text', 'Text message', true);
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(18, strlen($params[0]));
        });
        $written = $handler->push($frame, true);
        $this->assertEquals(18, $written);

        fclose($temp);
    }

    public function testPullMaskedFrame(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $handler = new FrameHandler($stream, false, true);
        $this->assertInstanceOf(FrameHandler::class, $handler);

        $this->expectSocketStreamRead()->addAssert(function ($method, $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('gYw=');
        });
        $this->expectSocketStreamRead()->addAssert(function ($method, $params) {
            $this->assertEquals(4, $params[0]);
        })->setReturn(function () {
            return base64_decode('kWqxiw==');
        });
        $this->expectSocketStreamRead()->addAssert(function ($method, $params) {
            $this->assertEquals(12, $params[0]);
        })->setReturn(function () {
            return base64_decode('xQ/J/7EH1PjiC9bu');
        });
        $frame = $handler->pull();
        $this->assertEquals('Text message', $frame->getPayload());
        $this->assertEquals('text', $frame->getOpcode());

        fclose($temp);
    }

    public function testPushPayload128(): void
    {
        $temp = tmpfile();
        $payload = file_get_contents(__DIR__ . '/../../mock/payload.128.txt');

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $handler = new FrameHandler($stream, false, false);
        $this->assertInstanceOf(FrameHandler::class, $handler);

        $frame = new Frame('text', $payload, true);
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) use ($payload) {
            $this->assertEquals(132, strlen($params[0]));
            $this->assertEquals(base64_decode('gX4='), substr($params[0], 0, 2));
            $this->assertEquals(base64_decode('AIA='), substr($params[0], 2, 2));
            $this->assertEquals($payload, substr($params[0], 4));
        });
        $written = $handler->push($frame, false);
        $this->assertEquals(132, $written);

        fclose($temp);
    }

    public function testPullPayload128(): void
    {
        $temp = tmpfile();
        $payload = file_get_contents(__DIR__ . '/../../mock/payload.128.txt');

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $handler = new FrameHandler($stream, false, false);
        $this->assertInstanceOf(FrameHandler::class, $handler);

        $this->expectSocketStreamRead()->addAssert(function ($method, $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('gX4=');
        });
        $this->expectSocketStreamRead()->addAssert(function ($method, $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('AIA=');
        });
        $this->expectSocketStreamRead()->addAssert(function ($method, $params) {
            $this->assertEquals(128, $params[0]);
        })->setReturn(function () use ($payload) {
            return $payload;
        });
        $frame = $handler->pull();
        $this->assertEquals($payload, $frame->getPayload());
        $this->assertEquals(128, $frame->getPayloadLength());
        $this->assertEquals('text', $frame->getOpcode());

        fclose($temp);
    }

    public function testPushPayload65536(): void
    {
        $temp = tmpfile();
        $payload = file_get_contents(__DIR__ . '/../../mock/payload.65536.txt');

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $handler = new FrameHandler($stream, false, false);
        $this->assertInstanceOf(FrameHandler::class, $handler);

        $frame = new Frame('text', $payload, true);
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) use ($payload) {
            $this->assertEquals(65546, strlen($params[0]));
            $this->assertEquals(base64_decode('gX8='), substr($params[0], 0, 2));
            $this->assertEquals(base64_decode('AAAAAAABAAA='), substr($params[0], 2, 8));
            $this->assertEquals($payload, substr($params[0], 10));
        });
        $written = $handler->push($frame, false);
        $this->assertEquals(65546, $written);

        fclose($temp);
    }

    public function testPullPayload65536(): void
    {
        $temp = tmpfile();
        $payload = file_get_contents(__DIR__ . '/../../mock/payload.65536.txt');

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $handler = new FrameHandler($stream, false, false);
        $this->assertInstanceOf(FrameHandler::class, $handler);

        $this->expectSocketStreamRead()->addAssert(function ($method, $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('gX8=');
        });
        $this->expectSocketStreamRead()->addAssert(function ($method, $params) {
            $this->assertEquals(8, $params[0]);
        })->setReturn(function () {
            return base64_decode('AAAAAAABAAA=');
        });
        $this->expectSocketStreamRead()->addAssert(function ($method, $params) {
            $this->assertEquals(65536, $params[0]);
        })->setReturn(function () use ($payload) {
            return $payload;
        });
        $frame = $handler->pull();
        $this->assertEquals($payload, $frame->getPayload());
        $this->assertEquals(65536, $frame->getPayloadLength());
        $this->assertEquals('text', $frame->getOpcode());

        fclose($temp);
    }

    public function testWriteError(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $handler = new FrameHandler($stream, false, false);
        $this->assertInstanceOf(FrameHandler::class, $handler);

        $frame = new Frame('text', 'Failed message', true);
        $this->expectSocketStreamWrite()->setReturn(function () {
            return 0;
        });
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Could only write 0 out of 16 bytes.");
        $handler->push($frame, false);

        fclose($temp);
    }

    public function testReadError(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $handler = new FrameHandler($stream, false, false);
        $this->assertInstanceOf(FrameHandler::class, $handler);

        $this->expectSocketStreamRead()->addAssert(function ($method, $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return '';
        });
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Empty read; connection dead?");
        $handler->pull();

        fclose($temp);
    }
}
