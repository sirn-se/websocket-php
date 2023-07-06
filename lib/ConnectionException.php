<?php

/**
 * Copyright (C) 2014-2022 Textalk/Abicart and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/Textalk/websocket-php/master/COPYING
 */

namespace WebSocket;

use Throwable;

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
