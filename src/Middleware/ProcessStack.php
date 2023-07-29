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
    Message,
    MessageHandler
};

/**
 * WebSocket\Middleware\ProcessStack class.
 * Worker stack for middleware implementations.
 */
class ProcessStack
{
    private $connection;
    private $messageHandler;
    private $processors;

    /**
     * Create ProcessStack.
     * @param Connection $connection
     * @param MessageHandler $messageHandler
     * @param array $processors
     */
    public function __construct(Connection $connection, MessageHandler $messageHandler, array $processors)
    {
        $this->connection = $connection;
        $this->messageHandler = $messageHandler;
        $this->processors = $processors;
    }

    /**
     * Process middleware for incoming message.
     * @return Message
     */
    public function handleIncoming(): Message
    {
        $processor = array_shift($this->processors);
        if ($processor) {
            return $processor->processIncoming($this, $this->connection);
        }
        return $this->messageHandler->pull();
    }

    /**
     * Process middleware for outgoing message.
     * @param Message $message
     * @return Message
     */
    public function handleOutgoing(Message $message): Message
    {
        $processor = array_shift($this->processors);
        if ($processor) {
            return $processor->processOutgoing($this, $this->connection, $message);
        }
        return $this->messageHandler->push($message, $this->connection->getFrameSize());
    }
}
