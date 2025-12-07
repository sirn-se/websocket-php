<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Middleware;

use Stringable;
use WebSocket\Connection;
use WebSocket\Message\{
    Close,
    Message
};
use WebSocket\Trait\{
    ConfigurationTrait,
    StringableTrait,
};

/**
 * WebSocket\Middleware\CloseHandler class.
 * Handles close procedure.
 */
class CloseHandler implements ProcessIncomingInterface, ProcessOutgoingInterface, Stringable
{
    use ConfigurationTrait;
    use StringableTrait;

    public function __construct()
    {
        $this->initConfiguration();
    }

    public function processIncoming(ProcessStack $stack, Connection $connection): Message
    {
        $message = $stack->handleIncoming(); // Proceed before logic
        if (!$message instanceof Close) {
            return $message;
        }
        if ($connection->isWritable()) {
            // Remote sent Close; acknowledge and close for further reading
            $this->configuration->getLogger()->debug('[close-handler] Received \'close\', status: {status}', [
                'status' => $message->getCloseStatus(),
            ]);
            $ack =  "Close acknowledged: {$message->getCloseStatus()}";
            $connection->closeRead();
            $connection->send(new Close($message->getCloseStatus(), $ack));
        } else {
            // Remote sent Close/Ack: disconnect
            $this->configuration->getLogger()->debug('[close-handler] Received \'close\', acknowledge, disconnecting');
            $connection->disconnect();
        }
        return $message;
    }

    public function processOutgoing(ProcessStack $stack, Connection $connection, Message $message): Message
    {
        $message = $stack->handleOutgoing($message); // Proceed before logic
        if (!$message instanceof Close) {
            return $message;
        }
        if ($connection->isReadable()) {
            // Local sent Close: close for further writing, expect remote acknowledge
            $this->configuration->getLogger()->debug('[close-handler] Sent \'close\', status: {status}', [
                'status' => $message->getCloseStatus(),
            ]);
            $connection->closeWrite();
        } else {
            // Local sent Close/Ack: disconnect
            $this->configuration->getLogger()->debug('[close-handler] Sent \'close\', acknowledge, disconnecting');
            $connection->disconnect();
        }
        return $message;
    }
}
