<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

use RuntimeException;
use Throwable;

/**
 * WebSocket\Exception\AbstractException abstract class.
 * Core exception for repo
 */
abstract class AbstractException extends RuntimeException implements ExceptionInterface
{
    protected static string $defaultMessage = 'Unspecified error';

    public function __construct(
        string|null $message = null,
        Throwable|null $previous = null,
    ) {
        parent::__construct($message ?? static::$defaultMessage, 0, $previous);
    }
}
