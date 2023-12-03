<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Middleware;

use WebSocket\Connection;
use WebSocket\Http\Message;

/**
 * WebSocket\Middleware\ProcessHttpIncomingInterface interface.
 * Interface for incoming middleware implementations.
 */
interface ProcessHttpIncomingInterface extends MiddlewareInterface
{
    public function processHttpIncoming(ProcessHttpStack $stack, Connection $connection): Message;
}
