<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Message;

/**
 * WebSocket\Message\Binary class.
 * A Binary WebSocket message.
 */
class Binary extends Message
{
    protected $opcode = 'binary';
}
