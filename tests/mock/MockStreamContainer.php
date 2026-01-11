<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Test;

use Phrity\Net\{
    StreamInterface,
    StreamContainerInterface,
};
use Phrity\Net\Mock\{
    Stream,
    StreamFactory,
};

/**
 * This class is used by phpunit tests to mock and track various socket/stream calls.
 */
class MockStreamContainer implements StreamContainerInterface
{
    private StreamInterface $stream;

    public function __construct(StreamFactory $factory)
    {
        $this->stream = $factory->createStream('mock-stream-container');
    }

    public function getStream(): StreamInterface
    {
        return $this->stream;
    }

    public function getIdentity(): string
    {
        return 'mock-stream-container';
    }
}
