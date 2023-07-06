<?php

/**
 * Copyright (C) 2014-2022 Textalk/Abicart and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/Textalk/websocket-php/master/COPYING
 */

namespace WebSocket;

class BadOpcodeException extends Exception
{
    public function __construct(string $message, int $code = self::BAD_OPCODE, Throwable $prev = null)
    {
        parent::__construct($message, $code, $prev);
    }
}
