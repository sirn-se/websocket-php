<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket;

use Throwable;

/**
 * WebSocket\ConnectionException class.
 * Thrown when connection operation fails.
 */
class ConnectionException extends Exception
{
    private $data;

    public function __construct(string $message, int $code = 0, array $data = [], Throwable $prev = null)
    {
        parent::__construct($message, $code, $prev);
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
