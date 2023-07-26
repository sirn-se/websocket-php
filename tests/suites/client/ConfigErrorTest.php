<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

declare(strict_types=1);

namespace WebSocket\Test\Client;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\StreamFactory;
use Phrity\Net\Mock\Stack\{
    ExpectSocketClientTrait,
    ExpectSocketStreamTrait,
    ExpectStreamFactoryTrait
};
use WebSocket\{
    Client,
    BadUriException
};
use WebSocket\Test\MockStreamTrait;

/**
 * Test case for WebSocket\Client: Setup & configuration errors.
 */
class ConfigErrorTest extends TestCase
{
    use ExpectSocketClientTrait;
    use ExpectSocketStreamTrait;
    use ExpectStreamFactoryTrait;
    use MockStreamTrait;

    public function setUp(): void
    {
        error_reporting(-1);
        $this->setUpStack();
    }

    public function tearDown(): void
    {
        $this->tearDownStack();
    }

    public function testUriInvalid(): void
    {
        $this->expectException(BadUriException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Invalid URI '--:this is not an uri:--' provided.");
        $client = new Client('--:this is not an uri:--');
    }

    public function testUriInvalidScheme(): void
    {
        $this->expectException(BadUriException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Invalid URI scheme, must be 'ws' or 'wss'.");
        $client = new Client('bad://localhost:8000/my/mock/path');
    }

    public function testUriInvalidType(): void
    {
        $this->expectException(BadUriException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Provided URI must be a UriInterface or string.");
        $client = new Client([]);
    }

    public function testUriInvalidHost(): void
    {
        $this->expectException(BadUriException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Invalid URI host.");
        $client = new Client('ws:///my/mock/path');
    }

    public function testContextOptionInvald(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path', ['context' => 'BAD']);
        $client->setStreamFactory(new StreamFactory());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Stream context option is invalid.');
        $client->connect();

        $this->expectSocketStreamClose();
        unset($client);
    }
}
