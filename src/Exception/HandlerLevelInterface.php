<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

use WebSocket\Runtime\HandlerInterface;

/**
 * WebSocket\Exception\HandlerLevelInterface interface.
 * Indicates error on handler level - handler should be closed
 */
interface HandlerLevelInterface extends ExceptionInterface
{
    public function getHandler(): HandlerInterface|null;
}
