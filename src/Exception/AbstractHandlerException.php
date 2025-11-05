<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

use RuntimeException;
use WebSocket\Runtime\HandlerInterface;
use Throwable;

/**
 * WebSocket\Exception\Exception abstract class.
 * Abstract for handler level exceptions.
 */
abstract class AbstractHandlerException extends AbstractException implements HandlerLevelInterface
{
    protected static string $defaultMessage = 'Unspecified handler error';

    private HandlerInterface|null $handler;

    public function __construct(
        HandlerInterface|null $handler = null,
        string|null $message = null,
        Throwable|null $previous = null,
    ) {
        $this->handler = $handler;
        parent::__construct($message, $previous);
    }

    public function getHandler(): HandlerInterface|null
    {
        return $this->handler;
    }
}
