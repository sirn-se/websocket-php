<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Runtime;

use Phrity\Net\Mock\StreamFactory;
use PHPUnit\Framework\TestCase;
use WebSocket\Exception\RunnerException;
use WebSocket\Runtime\Runner;
use WebSocket\Test\{
    MockStreamContainer,
    MockStreamTrait,
};

/**
 * Test case for WebSocket\Runtime\Runner
 */
class RunnerTest extends TestCase
{
    use MockStreamTrait;

    public function setUp(): void
    {
        $this->setUpStack();
    }

    public function tearDown(): void
    {
        $this->tearDownStack();
    }

    public function testHandling(): void
    {
        $this->expectStreamFactory();
        $factory = new StreamFactory();
        $this->expectStreamFactoryCreateStreamCollection();
        $this->expectStreamCollection();
        $runner = new Runner($factory);

        $this->expectStreamFactoryCreateStream();
        $this->expectStreamFactoryCreateStreamFromResource();
        $this->expectStream();
        $this->expectStreamGetMetadata();
        $this->expectContext();
        $streamContainer = new MockStreamContainer($factory);

        $this->expectStreamCollectionAttach();
        $runner->attach($streamContainer, function () {
            // ignore
        });
        $this->expectStreamCollectionWaitRead();
        $this->expectStreamCollection();
        $runner->handle(0);

        $this->expectStreamCollectionDetach();
        $runner->detach($streamContainer->getIdentity());
    }

    public function testIdentityConflict(): void
    {
        $this->expectStreamFactory();
        $factory = new StreamFactory();
        $this->expectStreamFactoryCreateStreamCollection();
        $this->expectStreamCollection();
        $runner = new Runner($factory);

        $this->expectStreamFactoryCreateStream();
        $this->expectStreamFactoryCreateStreamFromResource();
        $this->expectStream();
        $this->expectStreamGetMetadata();
        $this->expectContext();
        $streamContainer = new MockStreamContainer($factory);

        $this->expectStreamCollectionAttach();
        $runner->attach($streamContainer, function () {
            // ignore
        });
        $this->expectException(RunnerException::class);
        $this->expectExceptionMessage('Stream container with identity mock-stream-container already attached');
        $runner->attach($streamContainer, function () {
            // ignore
        });
    }
}
