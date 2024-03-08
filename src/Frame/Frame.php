<?php

/**
 * Copyright (C) 2014-2024 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Frame;

use Stringable;
use WebSocket\Trait\StringableTrait;

/**
 * WebSocket\Frame\Frame class.
 * Represent a single frame sent or received as part of websocket message.
 */
class Frame implements Stringable
{
    use StringableTrait;

    private $opcode;
    private $payload;
    private $final;

    public function __construct(string $opcode, string $payload, bool $final)
    {
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

    public function __toString(): string
    {
        return $this->stringable('%s', $this->opcode);
    }
}
