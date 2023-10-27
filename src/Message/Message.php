<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Message;

use DateTimeImmutable;
use DateTimeInterface;
use WebSocket\Frame\Frame;

/**
 * WebSocket\Message\Message class.
 * Abstract superclass for WebSocket messages.
 */
abstract class Message
{
    protected $opcode;
    protected $content;
    protected $timestamp;

    public function __construct(string $content = '')
    {
        $this->content = $content;
        $this->timestamp = new DateTimeImmutable();
    }

    public function getOpcode(): string
    {
        return $this->opcode;
    }

    public function getLength(): int
    {
        return strlen($this->content);
    }

    public function getTimestamp(): DateTimeInterface
    {
        return $this->timestamp;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content = ''): void
    {
        $this->content = $content;
    }

    public function hasContent(): bool
    {
        return $this->content != '';
    }

    public function __toString(): string
    {
        return get_class($this);
    }

    public function getPayload(): string
    {
        return $this->content;
    }

    public function setPayload(string $payload = ''): void
    {
        $this->content = $payload;
    }

    // Split messages into frames
    public function getFrames(int $frameSize = 4096): array
    {
        $frames = [];
        $split = str_split($this->getPayload(), $frameSize) ?: [''];
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
