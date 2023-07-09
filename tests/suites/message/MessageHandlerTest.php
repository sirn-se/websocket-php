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
use Phrity\Net\Mock\SocketStream;
use Phrity\Net\Mock\Stack\ExpectSocketStreamTrait;
use WebSocket\ConnectionException;
use WebSocket\Frame\{
    Frame,
    FrameHandler
};
use WebSocket\Message\{
    Text,
    MessageHandler
};

/**
 * Test case for WebSocket\Message\MessageHandler.
 */
class MessageHandlerTest extends TestCase
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

    public function testPushUnmasked(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $handler = new MessageHandler(new FrameHandler(new SocketStream($temp)));
        $this->assertInstanceOf(MessageHandler::class, $handler);

        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(14, strlen($params[0]));
            $this->assertEquals(base64_decode('gQxUZXh0IG1lc3NhZ2U='), $params[0]);
            $this->assertEquals('Text message', substr($params[0], 2));
        });
        $message = new Text('Text message');
        $handler->push($message, false);

        fclose($temp);
    }

    public function testPullUnmasked(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $handler = new MessageHandler(new FrameHandler(new SocketStream($temp)));
        $this->assertInstanceOf(MessageHandler::class, $handler);

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
        $message = $handler->pull();
        $this->assertEquals('Text message', $message->getContent());
        $this->assertEquals('text', $message->getOpcode());

        fclose($temp);
    }

    public function testPushMasked(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $handler = new MessageHandler(new FrameHandler(new SocketStream($temp)));
        $this->assertInstanceOf(MessageHandler::class, $handler);

        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(18, strlen($params[0]));
        });
        $message = new Text('Text message');
        $handler->push($message, true);

        fclose($temp);
    }

    public function testPullMasked(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $handler = new MessageHandler(new FrameHandler(new SocketStream($temp)));
        $this->assertInstanceOf(MessageHandler::class, $handler);

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
        $message = $handler->pull();
        $this->assertEquals('Text message', $message->getContent());
        $this->assertEquals('text', $message->getOpcode());

        fclose($temp);
    }

    public function testPushFragmented(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $handler = new MessageHandler(new FrameHandler(new SocketStream($temp)));
        $this->assertInstanceOf(MessageHandler::class, $handler);

        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(7, strlen($params[0]));
            $this->assertEquals('Text ', substr($params[0], 2));
        });
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(7, strlen($params[0]));
            $this->assertEquals('messa', substr($params[0], 2));
        });
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(4, strlen($params[0]));
            $this->assertEquals('ge', substr($params[0], 2));
        });
        $message = new Text('Text message');
        $handler->push($message, false, 5);

        fclose($temp);
    }

    public function testPullFragmented(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $handler = new MessageHandler(new FrameHandler(new SocketStream($temp)));
        $this->assertInstanceOf(MessageHandler::class, $handler);

        $this->expectSocketStreamRead()->addAssert(function ($method, $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('AQU=');
        });
        $this->expectSocketStreamRead()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
        })->setReturn(function () {
            return base64_decode('VGV4dCA=');
        });
        $this->expectSocketStreamRead()->addAssert(function ($method, $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('AAU=');
        });
        $this->expectSocketStreamRead()->addAssert(function ($method, $params) {
            $this->assertEquals(5, $params[0]);
        })->setReturn(function () {
            return base64_decode('bWVzc2E=');
        });
        $this->expectSocketStreamRead()->addAssert(function ($method, $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('gAI=');
        });
        $this->expectSocketStreamRead()->addAssert(function ($method, $params) {
            $this->assertEquals(2, $params[0]);
        })->setReturn(function () {
            return base64_decode('Z2U=');
        });
        $message = $handler->pull();
        $this->assertEquals('Text message', $message->getContent());
        $this->assertEquals('text', $message->getOpcode());

        fclose($temp);
    }
}
