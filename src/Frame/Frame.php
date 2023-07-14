<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Frame;

use WebSocket\{
    BadOpcodeException,
    OpcodeTrait
};

/**
 * WebSocket\Frame\Frame class.
 * Represent a single frame sent or received as part of websocket message.
 */
class Frame
{
    use OpcodeTrait;

    private $opcode;
    private $payload;
    private $final;

    public function __construct(string $opcode, string $payload, bool $final)
    {
        if (!array_key_exists($opcode, self::$opcodes)) {
            throw new BadOpcodeException("Invalid opcode '{$opcode}' provided");
        }
        $this->opcode = $opcode;
        $this->payload = $payload;
        $this->final = $final;
    }

    public function isFinal(): bool
    {
        return $this->final;
    }

    public function isContinuation(): bool
    {
        return $this->opcode === 'continuation';
    }

    public function getOpcode(): string
    {
        return $this->opcode;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function getPayloadLength(): int
    {
        return strlen($this->payload);
    }
}
