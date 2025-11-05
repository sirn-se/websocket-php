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
 * Core exception for repo
 */
abstract class AbstractHandlerException extends AbstractException implements HandlerLevelInterface
{
    private HandlerInterface $handler;

    public function __construct(
        HandlerInterface $handler,
        string $message,
        Throwable|null $previous = null,
    ) {
        $this->handler = $handler;
        parent::__construct($message, 0, $previous);
    }

    public function getHandler(): HandlerInterface
    {
        return $this->handler;
    }
}
