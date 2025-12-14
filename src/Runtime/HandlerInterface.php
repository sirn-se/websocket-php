<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Runtime;

use WebSocket\Connection;

/**
 * WebSocket\Runtime\HandlerInterface interface.
 */
interface HandlerInterface extends IdentityInterface
{
    public function getIdentity(): string;
    public function selectHandler(Connection $connection): void;
    public function beforeStart(): void;
    public function beforeWatch(): void;
    public function afterWatch(): void;
    public function disconnect(): void;
}
