<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Message;

/**
 * WebSocket\Message\Close class.
 * A Close WebSocket message.
 */
class Close extends Message
{
    protected $opcode = 'close';
}
