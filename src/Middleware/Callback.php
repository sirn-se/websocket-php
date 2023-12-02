<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Middleware;

use Closure;
use Psr\Log\{
    LoggerAwareInterface,
    LoggerAwareTrait
};
use Stringable;
use WebSocket\Connection;
use WebSocket\Http\{
    HttpHandler,
    Message as HttpMessage
};
use WebSocket\Message\Message;

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
    Stringable
{
    use LoggerAwareTrait;

    private $incoming;
    private $outgoing;
    private $httpIncoming;
    private $httpOutgoing;

    public function __construct(
        Closure|null $incoming = null,
        Closure|null $outgoing = null,
        Closure|null $httpIncoming = null,
        Closure|null $httpOutgoing = null,
    ) {
        $this->incoming = $incoming;
        $this->outgoing = $outgoing;
        $this->httpIncoming = $httpIncoming;
        $this->httpOutgoing = $httpOutgoing;
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

    public function processHttpIncoming(ProcessHttpStack $stack, Connection $connection): HttpMessage
    {
        if (is_callable($this->httpIncoming)) {
            return call_user_func($this->httpIncoming, $stack, $connection);
        }
        return $stack->handleIncoming();
    }

    public function processHttpOutgoing(
        ProcessHttpStack $stack,
        Connection $connection,
        HttpMessage $message
    ): HttpMessage {
        if (is_callable($this->httpOutgoing)) {
            return call_user_func($this->httpOutgoing, $stack, $connection, $message);
        }
        return $stack->handleOutgoing($message);
    }

    public function __toString(): string
    {
        return get_class($this);
    }
}
