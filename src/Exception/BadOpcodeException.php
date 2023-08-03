<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Exception;

/**
 * WebSocket\Exception\BadOpcodeException class.
 * Thrown when bad opcode is sent or received.
 */
class BadOpcodeException extends Exception implements MessageLevelInterface
{
    public function __construct(string $message = '')
    {
        parent::__construct($message ?: 'Bad Opcode');
    }
}
