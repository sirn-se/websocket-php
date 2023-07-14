<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket;

/**
 * WebSocket\BadOpcodeException class.
 * Thrown when bad opcode is sent or received.
 */
class BadOpcodeException extends Exception
{
    public function __construct(string $message, int $code = self::BAD_OPCODE, Throwable $prev = null)
    {
        parent::__construct($message, $code, $prev);
    }
}
