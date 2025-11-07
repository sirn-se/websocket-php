<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

/**
 * WebSocket\Exception\ConnectionFailureException class.
 * Unspecified error on connection.
 */
class ConnectionFailureException extends AbstractConnectionException
{
    protected static string $defaultMessage = 'Connection error';
}
