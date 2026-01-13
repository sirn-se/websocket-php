<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Connection;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\SocketStream;
use Phrity\Net\Mock\Stack\{
    ExpectContextTrait,
    ExpectSocketStreamTrait,
};
use RuntimeException;
use WebSocket\Connection;
use WebSocket\Exception\{
    BadOpcodeException,
    BadUriException,
    ConnectionClosedException,
    ConnectionFailureException,
    ConnectionTimeoutException
};
use WebSocket\Message\Text;

/**
 * Test case for WebSocket\Connection: Exceptions.
 */
class ExceptionTest extends TestCase
{
    use ExpectContextTrait;
    use ExpectSocketStreamTrait;

    public function setUp(): void
    {
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
        $this->expectContext();
        $stream = new SocketStream($temp);
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = new Connection($stream, false, false);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new BadOpcodeException();
        });

        $this->expectException(BadOpcodeException::class);
        $this->expectExceptionMessage('Bad Opcode');
        $connection->send(new Text('Bad Opcode'));
    }

    public function testBadUriException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = new Connection($stream, false, false);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new BadUriException();
        });
        $this->expectException(BadUriException::class);
        $this->expectExceptionMessage('Bad URI');
        $connection->send(new Text('Bad URI'));
    }

    public function testConnectionClosedException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = new Connection($stream, false, false);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new ConnectionClosedException();
        });
        $this->expectException(ConnectionClosedException::class);
        $this->expectExceptionMessage('Connection has unexpectedly closed');
        $connection->send(new Text('Connection has unexpectedly closed'));
    }

    public function testConnectionFailureException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = new Connection($stream, false, false);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new ConnectionFailureException();
        });
        $this->expectException(ConnectionFailureException::class);
        $this->expectExceptionMessage('Connection error');
        $connection->send(new Text('Connection error'));
    }

    public function testConnectionTimeoutException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = new Connection($stream, false, false);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new ConnectionTimeoutException();
        });
        $this->expectException(ConnectionTimeoutException::class);
        $this->expectExceptionMessage('Connection operation timeout');
        $connection->send(new Text('Connection operation timeout'));
    }

    public function testGenericTimeoutException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = new Connection($stream, false, false);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new RuntimeException('Generic error', 77);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamGetMetadata()->setReturn(function () {
            return ['timed_out' => true, 'eof' => false];
        });
        $this->expectException(ConnectionTimeoutException::class);
        $this->expectExceptionMessage('Connection operation timeout');
        $connection->send(new Text('Timeout'));
    }

    public function testGenericEofException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = new Connection($stream, false, false);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new RuntimeException('Generic error', 77);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamGetMetadata()->setReturn(function () {
            return ['timed_out' => false, 'eof' => true];
        });
        $this->expectException(ConnectionClosedException::class);
        $this->expectExceptionMessage('Connection has unexpectedly closed');
        $connection->send(new Text('Eof'));
    }

    public function testGenericUnconnectedException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = new Connection($stream, false, false);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new RuntimeException('Generic error', 77);
        });
        $this->expectSocketStreamIsConnected()->setReturn(function () {
            return false;
        });
        $this->expectException(ConnectionFailureException::class);
        $this->expectExceptionMessage('Connection error');
        $connection->send(new Text('Generic'));
    }

    public function testGenericConnectedException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = new Connection($stream, false, false);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new RuntimeException('Generic error', 77);
        });
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamGetMetadata()->setReturn(function () {
            return ['timed_out' => false, 'eof' => false];
        });
        $this->expectException(ConnectionFailureException::class);
        $this->expectExceptionMessage('Connection error');
        $connection->send(new Text('Generic'));
    }

    public function testInvalidTimeout(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = new Connection($stream, false, false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid timeout '-1' provided");
        $connection->setTimeout(-1);
    }

    public function testInvalidFrameSize(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = new Connection($stream, false, false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid frameSize '0' provided");
        // @phpstan-ignore argument.type
        $connection->setFrameSize(0);
    }
}
