<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Message;

use WebSocket\BadOpcodeException;

/**
 * WebSocket\Message\Factory class.
 * Helper class to create Message instances.
 */
class Factory
{
    public function create(string $opcode, string $payload = ''): Message
    {
        switch ($opcode) {
            case 'text':
                return new Text($payload);
            case 'binary':
                return new Binary($payload);
            case 'ping':
                return new Ping($payload);
            case 'pong':
                return new Pong($payload);
            case 'close':
                return new Close($payload);
        }
        throw new BadOpcodeException("Invalid opcode '{$opcode}' provided");
    }
}
