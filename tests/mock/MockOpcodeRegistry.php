<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Test;

use WebSocket\Message\OpcodeRegistry;

/**
 * This class adds invalid opcode/class mappings for testing
 */
class MockOpcodeRegistry extends OpcodeRegistry
{
    public function mockBind(int $opcode, string $classname): void
    {
        // @phpstan-ignore assign.propertyType
        $this->map[$opcode] = $classname;
    }
}
