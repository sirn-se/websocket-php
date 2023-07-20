<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Middleware;

use WebSocket\Connection;
use WebSocket\Message\Message;

/**
 * WebSocket\Middleware\ProcessOutgoingInterface interface.
 * Interface for outgoing middleware implementations.
 */
interface ProcessOutgoingInterface extends MiddlewareInterface
{
    public function processOutgoing(ProcessStack $stack, Connection $connection, Message $message): Message;
}
