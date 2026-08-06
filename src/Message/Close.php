<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Message;

/**
 * WebSocket\Message\Close class.
 * A Close WebSocket message.
 */
class Close extends Message
{
    protected string $opcode = 'close';
    /** @var int<0, 4999>|null $status */
    protected int|null $status = null;

    /**
     * @param int<0, 4999>|null $status
     */
    public function __construct(int|null $status = null, string $content = '')
    {
        $this->status = $status;
        parent::__construct($content);
    }

    /**
     * @return int<0, 4999>|null
     */
    public function getCloseStatus(): int|null
    {
        return $this->status;
    }

    /**
     * @param int<0, 4999>|null $status
     */
    public function setCloseStatus(int|null $status): void
    {
        $this->status = $status;
    }

    public function getPayload(): string
    {
        return pack("n", $this->status) . $this->content;
    }

    public function setPayload(string $payload = ''): void
    {
        $this->status = 0;
        $this->content = '';
        if (strlen($payload) > 0) {
            $this->status = current(unpack('n', $payload) ?: []);
        }
        if (strlen($payload) > 2) {
            $this->content = substr($payload, 2);
        }
    }
}
