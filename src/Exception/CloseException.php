<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

/**
 * WebSocket\Exception\CloseException class.
 * Connection should close
 */
class CloseException extends AbstractException implements ControlInterface
{
    protected static string $defaultMessage = 'Closing connection ({status})';

    /** @var int<0, 4999> $status */
    protected int $status;

    /**
     * @param int<0, 4999> $status
     */
    public function __construct(int $status = 1000, string|null $message = null)
    {
        $this->status = $status;
        parent::__construct($message, context: ['status' => $status]);
    }

    /**
     * @return int<0, 4999>
     */
    public function getCloseStatus(): int
    {
        return $this->status;
    }
}
