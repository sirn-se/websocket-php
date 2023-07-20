<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Middleware;

use WebSocket\Connection;
use WebSocket\Message\{
    Ping,
    Pong,
    Message
};

/**
 * WebSocket\Middleware\PingResponder class.
 * Responds on incoming ping messages.
 */
class PingResponder implements ProcessIncomingInterface
{
    public function processIncoming(ProcessStack $stack, Connection $connection): Message
    {
        $message = $stack->handleIncoming();
        if ($message instanceof Ping) {
            $connection->pushMessage(new Pong($message->getContent()));
        }
        return $message;
    }

    public function __toString(): string
    {
        return get_class($this);
    }
}
