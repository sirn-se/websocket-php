<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Middleware;

use WebSocket\Connection;
use WebSocket\Message\Message;

/**
 * WebSocket\Middleware\ProcessStack class.
 * Worker stack for middleware implementations.
 */
class ProcessStack
{
    private $connection;
    private $processors;
    private $cb;

    public function __construct(Connection $connection, array $processors, $cb)
    {
        $this->connection = $connection;
        $this->processors = $processors;
        $this->cb = $cb;
    }

    public function handleIncoming(): Message
    {
        $processor = array_shift($this->processors);
        if ($processor) {
            return $processor->processIncoming($this, $this->connection);
        }
        return call_user_func($this->cb);
    }

    public function handleOutgoing(Message $message): Message
    {
        $processor = array_shift($this->processors);
        if ($processor) {
            return $processor->processOutgoing($this, $this->connection, $message);
        }
        call_user_func($this->cb, $message);
        return $message;
    }
}
