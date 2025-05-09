<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Middleware;

use Closure;
use Psr\Http\Message\MessageInterface;
use Psr\Log\LoggerAwareInterface;
use Stringable;
use WebSocket\Connection;
use WebSocket\Message\Message;
use WebSocket\Trait\{
    LoggerAwareTrait,
    StringableTrait,
};

/**
 * WebSocket\Middleware\Callback class.
 * Generic middleware using callbacks.
 */
class Callback implements
    LoggerAwareInterface,
    ProcessHttpIncomingInterface,
    ProcessHttpOutgoingInterface,
    ProcessIncomingInterface,
    ProcessOutgoingInterface,
    ProcessTickInterface,
    Stringable
{
    use LoggerAwareTrait;
    use StringableTrait;

    private Closure|null $incoming;
    private Closure|null $outgoing;
    private Closure|null $httpIncoming;
    private Closure|null $httpOutgoing;
    private Closure|null $tick;

    public function __construct(
        Closure|null $incoming = null,
        Closure|null $outgoing = null,
        Closure|null $httpIncoming = null,
        Closure|null $httpOutgoing = null,
        Closure|null $tick = null,
    ) {
        $this->incoming = $incoming;
        $this->outgoing = $outgoing;
        $this->httpIncoming = $httpIncoming;
        $this->httpOutgoing = $httpOutgoing;
        $this->tick = $tick;
        $this->initLogger();
    }

    public function processIncoming(ProcessStack $stack, Connection $connection): Message
    {
        if (is_callable($this->incoming)) {
            return call_user_func($this->incoming, $stack, $connection);
        }
        return $stack->handleIncoming();
    }

    public function processOutgoing(ProcessStack $stack, Connection $connection, Message $message): Message
    {
        if (is_callable($this->outgoing)) {
            return call_user_func($this->outgoing, $stack, $connection, $message);
        }
        return $stack->handleOutgoing($message);
    }

    public function processHttpIncoming(ProcessHttpStack $stack, Connection $connection): MessageInterface
    {
        if (is_callable($this->httpIncoming)) {
            return call_user_func($this->httpIncoming, $stack, $connection);
        }
        return $stack->handleHttpIncoming();
    }

    public function processHttpOutgoing(
        ProcessHttpStack $stack,
        Connection $connection,
        MessageInterface $message
    ): MessageInterface {
        if (is_callable($this->httpOutgoing)) {
            return call_user_func($this->httpOutgoing, $stack, $connection, $message);
        }
        return $stack->handleHttpOutgoing($message);
    }

    public function processTick(ProcessTickStack $stack, Connection $connection): void
    {
        if (is_callable($this->tick)) {
            call_user_func($this->tick, $stack, $connection);
        }
        $stack->handleTick();
    }
}
