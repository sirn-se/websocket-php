<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Trait;

use Closure;
use Psr\Log\{
    LoggerAwareInterface,
    LoggerInterface,
    NullLogger,
};

/**
 * Implementation of LoggerAwareInterface.
 * Unlike PSR original, $logger should always be present.
 */
trait LoggerAwareTrait
{
    protected LoggerInterface $logger;

    /**
     * @param LoggerInterface|null $logger
     */
    public function initLogger(LoggerInterface|null $logger = null): void
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function attachLogger(mixed $instance): void
    {
        if ($instance instanceof LoggerAwareInterface) {
            $instance->setLogger($this->logger);
        }
    }
}
