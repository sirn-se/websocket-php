<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Test;

use WebSocket\Message\Message;

/**
 * This class is used by phpunit tests to test custom message types.
 */
class CustomMessage extends Message
{
    public static string $customOpcode = 'none';

    public function getOpcode(): string
    {
        return self::$customOpcode;
    }
}
