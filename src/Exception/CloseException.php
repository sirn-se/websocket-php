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
    /** @var array{status: int<0, 4999>} $defaultContext */
    protected static array $defaultContext = ['status' => 1000];

    /**
     * @return int<0, 4999>
     */
    public function getCloseStatus(): int
    {
        return $this->getContext('status');
    }
}
