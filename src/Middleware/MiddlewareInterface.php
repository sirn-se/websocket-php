<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Middleware;

use WebSocket\Configuration;
use Stringable;

/**
 * WebSocket\Middleware\MiddlewareInterface interface.
 * Interface for middleware implementations.
 */
interface MiddlewareInterface extends Stringable
{
    public function setConfiguration(Configuration $configuration): self;
}
