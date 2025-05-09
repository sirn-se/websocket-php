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
    protected Closure|null $observer;

    /**
     * @param LoggerInterface|null $logger
     * @param Closure(): array<mixed> $observer
     */
    public function initLogger(LoggerInterface|null $logger = null, Closure|null $observer = null): void
    {
        $this->logger = $logger ?? new NullLogger();
        $this->observer = $observer;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
        if ($this->observer instanceof Closure) {
            foreach (call_user_func($this->observer) as $instance) {
                $this->attachLogger($instance);
            }
        }
    }

    public function attachLogger(mixed $instance): void
    {
        if ($instance instanceof LoggerAwareInterface) {
            $instance->setLogger($this->logger);
        }
    }
}
