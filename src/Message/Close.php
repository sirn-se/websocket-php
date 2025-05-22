<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
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
    protected int|null $status = null;

    public function __construct(int|null $status = null, string $content = '')
    {
        $this->status = $status;
        parent::__construct($content);
    }

    public function getCloseStatus(): int|null
    {
        return $this->status;
    }

    public function setCloseStatus(int|null $status): void
    {
        $this->status = $status;
    }

    public function getPayload(): string
    {
        $statusBinstr = sprintf('%016b', $this->status);
        $statusStr = '';
        foreach (str_split($statusBinstr, 8) as $binstr) {
            $statusStr .= chr((int)bindec($binstr));
        }
        return $statusStr . $this->content;
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
