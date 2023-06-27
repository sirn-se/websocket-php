<?php

namespace WebSocket\Frame;

/**
 * WebSocket\Frame\Frame class.
 */
class Frame
{
    private $opcode;
    private $payload;
    private $final;
    private $masked;

    public function __construct(string $opcode, string $payload, bool $final, bool $masked)
    {
        $this->opcode = $opcode;
        $this->payload = $payload;
        $this->final = $final;
        $this->masked = $masked;
    }

    public function isFinal(): bool
    {
        return $this->final;
    }

    public function isContinuation(): bool
    {
        return $this->opcode === 'continuation';
    }

    public function isMasked(): bool
    {
        return $this->masked;
    }

    public function getOpcode(): string
    {
        return $this->opcode;
    }

    public function getContents(): string
    {
        return $this->payload;
    }

    public function getContentLength(): int
    {
        return strlen($this->payload);
    }
}
