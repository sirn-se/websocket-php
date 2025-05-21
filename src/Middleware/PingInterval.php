<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Middleware;

use Psr\Log\LoggerAwareInterface;
use Stringable;
use WebSocket\Connection;
use WebSocket\Message\{
    Ping,
    Message
};
use WebSocket\Trait\{
    LoggerAwareTrait,
    StringableTrait,
};

/**
 * WebSocket\Middleware\PingInterval class.
 * Handles close procedure.
 */
class PingInterval implements LoggerAwareInterface, ProcessOutgoingInterface, ProcessTickInterface, Stringable
{
    use LoggerAwareTrait;
    use StringableTrait;

    private int|float|null $interval;

    public function __construct(int|float|null $interval = null)
    {
        $this->interval = $interval;
        $this->initLogger();
    }

    public function processOutgoing(ProcessStack $stack, Connection $connection, Message $message): Message
    {
        $this->setNext($connection); // Update timestamp for next ping
        return $stack->handleOutgoing($message);
    }

    public function processTick(ProcessTickStack $stack, Connection $connection): void
    {
        // Push if time exceeds timestamp for next ping
        if ($connection->isWritable() && microtime(true) >= $this->getNext($connection)) {
            $this->logger->debug("[ping-interval] Auto-pushing ping");
            $connection->send(new Ping());
            $this->setNext($connection); // Update timestamp for next ping
        }
        $stack->handleTick();
    }

    private function getNext(Connection $connection): float
    {
        return $connection->getMeta('pingInterval.next') ?? $this->setNext($connection);
    }

    private function setNext(Connection $connection): float
    {
        $next = microtime(true) + ($this->interval ?? $connection->getTimeout());
        $connection->setMeta('pingInterval.next', $next);
        return $next;
    }
}
