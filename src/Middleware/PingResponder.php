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
    Pong,
    Message
};
use WebSocket\Trait\StringableTrait;

/**
 * WebSocket\Middleware\PingResponder class.
 * Responds on incoming ping messages.
 */
class PingResponder implements LoggerAwareInterface, ProcessIncomingInterface, Stringable
{
    use StringableTrait;
    use LoggerAwareTrait;

    public function processIncoming(ProcessStack $stack, Connection $connection): Message
    {
        $message = $stack->handleIncoming();
        if ($message instanceof Ping && $connection->isWritable()) {
            $connection->send(new Pong($message->getContent()));
        }
        return $message;
    }
}
