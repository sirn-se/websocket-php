<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

/**
 * WebSocket\Exception\ConnectionTimeoutException class.
 * Connection operation has timed out.
 */
class ConnectionTimeoutException extends AbstractException implements MessageLevelInterface
{
    protected static string $defaultMessage = 'Connection operation timeout';
}
