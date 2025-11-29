<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

/**
 * WebSocket\Exception\ConnectionTimeoutException class.
 * Connection operation has timed out.
 */
class ConnectionTimeoutException extends AbstractMessageException
{
    protected static string $defaultMessage = 'Connection operation timeout';
}
