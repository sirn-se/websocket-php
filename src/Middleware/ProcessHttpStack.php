<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Middleware;

use WebSocket\Connection;
use WebSocket\Http\{
    HttpHandler,
    Message
};

/**
 * WebSocket\Middleware\ProcessHttpStack class.
 * Worker stack for HTTP middleware implementations.
 */
class ProcessHttpStack
{
    private $connection;
    private $httpHandler;
    private $processors;

    /**
     * Create ProcessStack.
     * @param Connection $connection
     * @param HttpHandler $httpHandler
     * @param array $processors
     */
    public function __construct(Connection $connection, HttpHandler $httpHandler, array $processors)
    {
        $this->connection = $connection;
        $this->httpHandler = $httpHandler;
        $this->processors = $processors;
    }

    /**
     * Process middleware for incoming htpp message.
     * @return Message
     */
    public function handleIncoming(): Message
    {
        $processor = array_shift($this->processors);
        if ($processor) {
            return $processor->processHttpIncoming($this, $this->connection);
        }
        return $this->httpHandler->pull();
    }

    /**
     * Process middleware for outgoing htpp message.
     * @param Message $message
     * @return Message
     */
    public function handleOutgoing(Message $message): Message
    {
        $processor = array_shift($this->processors);
        if ($processor) {
            return $processor->processHttpOutgoing($this, $this->connection, $message);
        }
        return $this->httpHandler->push($message);
    }
}
