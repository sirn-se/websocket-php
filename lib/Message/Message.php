<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Message;

use DateTime;
use WebSocket\Frame\Frame;

/**
 * WebSocket\Message\Message class.
 * Abstract superclass for WebSocket messages.
 */
abstract class Message
{
    protected $opcode;
    protected $payload;
    protected $timestamp;

    public function __construct(string $payload = '')
    {
        $this->payload = $payload;
        $this->timestamp = new DateTime();
    }

    public function getOpcode(): string
    {
        return $this->opcode;
    }

    public function getLength(): int
    {
        return strlen($this->payload);
    }

    public function getTimestamp(): DateTime
    {
        return $this->timestamp;
    }

    public function getContent(): string
    {
        return $this->payload;
    }

    public function setContent(string $payload = ''): void
    {
        $this->payload = $payload;
    }

    public function hasContent(): bool
    {
        return $this->payload != '';
    }

    public function __toString(): string
    {
        return get_class($this);
    }

    // Split messages into frames
    public function getFrames(int $framesize = 4096): array
    {
        $frames = [];
        $split = str_split($this->getContent(), $framesize) ?: [''];
        foreach ($split as $i => $payload) {
            $frames[] = new Frame(
                $i === 0 ? $this->opcode : 'continuation',
                $payload,
                $i === array_key_last($split)
            );
        }
        return $frames;
    }
}
