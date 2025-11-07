<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

/**
 * WebSocket\Exception\CloseException class.
 * Connection should close
 */
class CloseException extends AbstractException implements ControlInterface
{
    protected static string $defaultMessage = 'Closing connection';

    protected int|null $status;

    public function __construct(int|null $status = null, string|null $message = null)
    {
        $this->status = $status;
        parent::__construct($message);
    }

    public function getCloseStatus(): int
    {
        return $this->status ?? 1000;
    }
}
