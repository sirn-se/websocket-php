<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket;

use Psr\Log\{
    LoggerAwareInterface,
    LoggerInterface,
    NullLogger,
};
use Stringable;
use WebSocket\Trait\StringableTrait;

/**
 * WebSocket\Connection class.
 * A client/server connection, wrapping socket stream.
 */
class Configuration implements LoggerAwareInterface, Stringable
{
    use StringableTrait;

    private LoggerInterface $logger;


    /* ---------- Magic methods ------------------------------------------------------------------------------------ */

    public function __construct(
        LoggerInterface|null $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function __toString(): string
    {
        return $this->stringable('');
    }


    /* ---------- Logger methods ----------------------------------------------------------------------------------- */

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
