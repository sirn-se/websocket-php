<?php

/**
 * Copyright (C) 2014-2024 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Middleware;

use Psr\Log\{
    LoggerAwareInterface,
    LoggerAwareTrait
};
use Stringable;
use WebSocket\Connection;
use WebSocket\Message\{
    Ping,
    Message
};
use WebSocket\Trait\StringableTrait;

/**
 * WebSocket\Middleware\PingInterval class.
 * Handles close procedure.
 */
class PingInterval implements LoggerAwareInterface, ProcessOutgoingInterface, ProcessTickInterface, Stringable
{
    use LoggerAwareTrait;
    use StringableTrait;

    private $interval;

    public function __construct(int|null $interval = null)
    {
        $this->interval = $interval;
    }

    public function processOutgoing(ProcessStack $stack, Connection $connection, Message $message): Message
    {
        $this->setNext($connection); // Update timestamp for next ping
        return $stack->handleOutgoing($message);
    }

    public function processTick(ProcessTickStack $stack, Connection $connection): void
    {
        // Push if time exceeds timestamp for next ping
        if ($connection->isWritable() && time() >= $this->getNext($connection)) {
            $this->logger->debug("[ping-interval] Auto-pushing ping");
            $connection->send(new Ping());
            $this->setNext($connection); // Update timestamp for next ping
        }
        $stack->handleTick();
    }

    private function getNext(Connection $connection): int
    {
        return $connection->getMeta('pingInterval.next') ?? $this->setNext($connection);
    }

    private function setNext(Connection $connection): int
    {
        $next = time() + ($this->interval ?? $connection->getTimeout());
        $connection->setMeta('pingInterval.next', $next);
        return $next;
    }
}
