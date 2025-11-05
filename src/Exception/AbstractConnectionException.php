<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

use WebSocket\Connection;
use WebSocket\Runtime\HandlerInterface;
use Throwable;

/**
 * WebSocket\Exception\AbstractConnecitonException abstract class.
 * Core exception for repo
 */
abstract class AbstractConnecitonException extends AbstractHandlerException implements ConnectionLevelInterface
{
    private Conneciton $connection;

    public function __construct(
        HandlerInterface $handler,
        Connection $connection,
        string $message,
        Throwable|null $previous = null,
    ) {
        $this->connection = $connection;
        parent::__construct($handler, $message, 0, $previous);
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
