<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Middleware;

use WebSocket\Connection;

/**
 * WebSocket\Middleware\ProcessTickStack class.
 * Worker stack for HTTP middleware implementations.
 */
class ProcessTickStack
{
    private $connection;
    private $processors;

    /**
     * Create ProcessStack.
     * @param Connection $connection
     * @param array $processors
     */
    public function __construct(Connection $connection, array $processors)
    {
        $this->connection = $connection;
        $this->processors = $processors;
    }

    /**
     * Process middleware for tick.
     * @return Message
     */
    public function handleTick(): void
    {
        $processor = array_shift($this->processors);
        if ($processor) {
            $processor->processTick($this, $this->connection);
        }
    }
}
