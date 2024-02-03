<?php

/**
 * Copyright (C) 2014-2024 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Middleware;

use Stringable;
use WebSocket\Connection;
use WebSocket\Message\{
    Message,
    MessageHandler
};
use WebSocket\Trait\StringableTrait;

/**
 * WebSocket\Middleware\ProcessStack class.
 * Worker stack for middleware implementations.
 */
class ProcessStack implements Stringable
{
    use StringableTrait;

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
