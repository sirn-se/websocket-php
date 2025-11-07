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
 * WebSocket\Exception\AbstractConnectionException abstract class.
 * Abstract for connection level exceptions.
 */
abstract class AbstractConnectionException extends AbstractException implements ConnectionLevelInterface
{
    protected static string $defaultMessage = 'Unspecified connection error';

    private Connection|null $connection;

    public function __construct(
        Connection|null $connection = null,
        string|null $message = null,
        Throwable|null $previous = null,
    ) {
        $this->connection = $connection;
        parent::__construct($message, $previous);
    }

    public function getHandler(): HandlerInterface|null
    {
        return $this->getConnection()?->getHandler();
    }

    public function getConnection(): Connection|null
    {
        return $this->connection;
    }
}
