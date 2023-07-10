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
use Phrity\Util\ErrorHandler;
use WebSocket\Connection;

/**
 * Test case for WebSocket\Connection: Deprecation warnings.
 */
class DeprecationTest extends TestCase
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

    public function testGetLineDeprecation(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $connection = new Connection($stream);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "something\n";
        });
        (new ErrorHandler())->withAll(function () use ($connection) {
           $connection->getLine(10, "\n");
        }, function ($exceptions, $result) {
            $this->assertEquals('getLine() on Connection is deprecated.', $exceptions[0]->getMessage());
        }, E_USER_DEPRECATED);

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();

        unset($stream);
    }

    public function testReadDeprecation(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $connection = new Connection($stream);

        $this->expectSocketStreamRead()->setReturn(function () {
            return "something\n";
        });
        (new ErrorHandler())->withAll(function () use ($connection) {
           $connection->read(2);
        }, function ($exceptions, $result) {
           $this->assertEquals('read() on Connection is deprecated.', $exceptions[0]->getMessage());
        }, E_USER_DEPRECATED);

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();

        unset($stream);
    }

    public function testWriteDeprecation(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $connection = new Connection($stream);

        $this->expectSocketStreamWrite();
        (new ErrorHandler())->withAll(function () use ($connection) {
           $connection->write('test');
        }, function ($exceptions, $result) {
           $this->assertEquals('write() on Connection is deprecated.', $exceptions[0]->getMessage());
        }, E_USER_DEPRECATED);

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();

        unset($stream);
    }

    public function testGetMetaDeprecation(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $connection = new Connection($stream);

        $this->expectSocketStreamGetMetadata();
        (new ErrorHandler())->withAll(function () use ($connection) {
           $connection->getMeta();
        }, function ($exceptions, $result) {
           $this->assertEquals('getMeta() on Connection is deprecated.', $exceptions[0]->getMessage());
        }, E_USER_DEPRECATED);

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();

        unset($stream);
    }

    public function testTellDeprecation(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $connection = new Connection($stream);

        $this->expectSocketStreamTell();
        (new ErrorHandler())->withAll(function () use ($connection) {
           $connection->tell();
        }, function ($exceptions, $result) {
           $this->assertEquals('tell() on Connection is deprecated.', $exceptions[0]->getMessage());
        }, E_USER_DEPRECATED);

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();

        unset($stream);
    }
/* @todo: Re-activate this test when expectSocketStreamEof is available
    public function testEofDeprecation(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $connection = new Connection($stream);

        $this->expectSocketStreamEof();
        (new ErrorHandler())->withAll(function () use ($connection) {
           $connection->eof();
        }, function ($exceptions, $result) {
           $this->assertEquals('eof() on Connection is deprecated.', $exceptions[0]->getMessage());
        }, E_USER_DEPRECATED);

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();

        unset($stream);
    }
*/
    public function testGetTypeDeprecation(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $stream = new SocketStream($temp);
        $connection = new Connection($stream);

        $this->expectSocketStreamGetResourceType();
        (new ErrorHandler())->withAll(function () use ($connection) {
            $type = $connection->getType();
            $this->assertEquals('stream', $type);
        }, function ($exceptions, $result) {
            $this->assertEquals('getType() on Connection is deprecated.', $exceptions[0]->getMessage());
        }, E_USER_DEPRECATED);

        $this->expectSocketStreamIsConnected();
        $this->expectSocketStreamClose();

        unset($stream);
    }
}
