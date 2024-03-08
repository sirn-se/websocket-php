<?php

/**
 * Copyright (C) 2014-2024 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

/**
 * WebSocket\Exception\ConnectionFailureException class.
 * Unspecified error on connection.
 */
class ConnectionFailureException extends Exception implements ConnectionLevelInterface
{
    public function __construct()
    {
        parent::__construct('Connection error');
    }
}
