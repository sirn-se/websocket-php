<?php

/**
 * Copyright (C) 2014-2024 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Connection;

use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\SocketStream;
use Phrity\Net\Mock\Stack\ExpectSocketStreamTrait;
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
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new BadOpcodeException();
        });

        $this->expectException(BadOpcodeException::class);
        $this->expectExceptionMessage('Bad Opcode');
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $connection->send(new Text('Bad Opcode'));

        unset($connection);
    }

    public function testBadUriException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new BadUriException();
        });
        $this->expectException(BadUriException::class);
        $this->expectExceptionMessage('Bad URI');
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $connection->send(new Text('Bad URI'));

        unset($connection);
    }

    public function testConnectionClosedException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new ConnectionClosedException();
        });
        $this->expectException(ConnectionClosedException::class);
        $this->expectExceptionMessage('Connection has unexpectedly closed');
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $connection->send(new Text('Connection has unexpectedly closed'));

        unset($connection);
    }

    public function testConnectionFailureException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new ConnectionFailureException();
        });
        $this->expectException(ConnectionFailureException::class);
        $this->expectExceptionMessage('Connection error');
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $connection->send(new Text('Connection error'));

        unset($connection);
    }

    public function testConnectionTimeoutException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new ConnectionTimeoutException();
        });
        $this->expectException(ConnectionTimeoutException::class);
        $this->expectExceptionMessage('Connection operation timeout');
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $connection->send(new Text('Connection operation timeout'));

        unset($connection);
    }

    public function testGenericTimeoutException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
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
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $connection->send(new Text('Timeout'));

        unset($connection);
    }

    public function testGenericEofException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
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
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $connection->send(new Text('Eof'));

        unset($connection);
    }

    public function testGenericUnconnectedException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $connection = new Connection($stream, false, false);

        $this->expectSocketStreamWrite()->setReturn(function () {
            throw new RuntimeException('Generic error', 77);
        });
        $this->expectSocketStreamIsConnected()->setReturn(function () {
            return false;
        });
        $this->expectException(ConnectionFailureException::class);
        $this->expectExceptionMessage('Connection error');
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $connection->send(new Text('Generic'));

        unset($connection);
    }

    public function testGenericConnectedException(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
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
        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();
        $connection->send(new Text('Generic'));

        unset($connection);
    }
}
