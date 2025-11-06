<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

use Throwable;

/**
 * WebSocket\Exception\AbstractConnectionException abstract class.
 * Abstract for connection level exceptions.
 */
abstract class AbstractMessageException extends AbstractException implements MessageLevelInterface
{
    protected static string $defaultMessage = 'Unspecified message error';
}
