<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

declare(strict_types=1);

namespace WebSocket\Test\Connection;

use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\SocketStream;
use Phrity\Net\Mock\Stack\ExpectSocketStreamTrait;
use RuntimeException;
use WebSocket\{
    Connection,
    Exception,
    BadOpcodeException,
    BadUriException,
    ConnectionException,
    TimeoutException
};
use WebSocket\Message\Text;

/**
 * Test case for WebSocket\Connection: Exceptions.
 */
class ExceptionTest extends TestCase
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

    public function testBadOpcodeException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $connection = new Connection($stream);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new BadOpcodeException('Bad Opcode', Exception::BAD_OPCODE);
        });
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $this->expectException(BadOpcodeException::class);
        $this->expectExceptionCode(Exception::BAD_OPCODE);
        $this->expectExceptionMessage('Bad Opcode');
        $connection->pushMessage(new Text('Bad Opcode'));

        unset($stream);
    }

    public function testBadUriException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $connection = new Connection($stream);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new BadUriException('Bad URI');
        });
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $this->expectException(BadUriException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Bad URI');
        $connection->pushMessage(new Text('Bad URI'));

        unset($stream);
    }

    public function testConnectionException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $connection = new Connection($stream);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new ConnectionException('Connection error', Exception::CLIENT_CONNECT_ERR);
        });
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $this->expectException(ConnectionException::class);
        $this->expectExceptionCode(Exception::CLIENT_CONNECT_ERR);
        $this->expectExceptionMessage('Connection error');
        $connection->pushMessage(new Text('Connection error'));

        unset($stream);
    }

    public function testTimeoutException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $connection = new Connection($stream);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new TimeoutException('Timeout', Exception::TIMED_OUT);
        });
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $this->expectException(TimeoutException::class);
        $this->expectExceptionCode(Exception::TIMED_OUT);
        $this->expectExceptionMessage('Timeout');
        $connection->pushMessage(new Text('Timeout'));

        unset($stream);
    }

    public function testGenericTimeoutException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $connection = new Connection($stream);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new RuntimeException('Generic error', 77);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamGetMetadata()->setReturn(function () {
            return ['timed_out' => true, 'eof' => false];
        });
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $this->expectException(TimeoutException::class);
        $this->expectExceptionCode(Exception::TIMED_OUT);
        $this->expectExceptionMessage('Connection timeout: Generic error');
        $connection->pushMessage(new Text('Timeout'));

        unset($stream);
    }

    public function testGenericEofException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $connection = new Connection($stream);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new RuntimeException('Generic error', 77);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamGetMetadata()->setReturn(function () {
            return ['timed_out' => false, 'eof' => true];
        });
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $this->expectException(ConnectionException::class);
        $this->expectExceptionCode(Exception::EOF);
        $this->expectExceptionMessage('Connection closed: Generic error');
        $connection->pushMessage(new Text('Eof'));

        unset($stream);
    }

    public function testGenericException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $connection = new Connection($stream);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new RuntimeException('Generic error', 77);
        });
        $this->expectSocketStreamIsConnected()->setReturn(function () {
            return false;
        });
        $this->expectSocketStreamClose();
        $this->expectSocketStreamIsConnected();
        $this->expectException(ConnectionException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Connection error: Generic error');
        $connection->pushMessage(new Text('Generic'));

        unset($stream);
    }

    public function testConnectionExceptionMethods(): void
    {
        $previous = new RuntimeException('Original error');
        $exception = new ConnectionException('Error', 0, ['test' => 'Test value'], $previous);
        $this->assertEquals(['test' => 'Test value'], $exception->getData());
    }
}
