<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Message;

use DateTimeImmutable;
use DateTimeInterface;
use Stringable;
use WebSocket\Exception\MessageEncodingException;
use WebSocket\Frame\Frame;
use WebSocket\Trait\StringableTrait;

/**
 * WebSocket\Message\MessageInterface interface.
 * Interface WebSocket messages.
 */
interface MessageInterface extends Stringable
{
    public function getOpcode(): string;

    public function getLength(): int;

    public function getTimestamp(): DateTimeInterface;

    public function getContent(): string;

    public function setContent(string $content = ''): void;

    public function hasContent(): bool;

    public function getPayload(): string;

    public function setPayload(string $payload = ''): void;

    public function isCompressed(): bool;

    /** @throws MessageEncodingException */
    public function setCompress(bool $compress): void;

    /**
     * @param int<1, max> $frameSize
     * @param OpcodeRegistry|null $opcodeRegistry
     * @return array<Frame>
     */
    public function getFrames(int $frameSize = 4096, OpcodeRegistry|null $opcodeRegistry = null): array;
}
