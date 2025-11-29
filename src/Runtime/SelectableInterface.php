<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Runtime;

use Phrity\Net\StreamContainerInterface;

/**
 * WebSocket\Runtime\SelectableInterface interface.
 */
interface SelectableInterface extends StreamContainerInterface
{
    public function onSelect(): void;
    public function getIdentity(): string;
}
