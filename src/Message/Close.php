<?php

/**
 * Copyright (C) 2014-2024 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Message;

/**
 * WebSocket\Message\Close class.
 * A Close WebSocket message.
 */
class Close extends Message
{
    protected $opcode = 'close';
    protected $status = null;

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
        $status_binstr = sprintf('%016b', $this->status);
        $status_str = '';
        foreach (str_split($status_binstr, 8) as $binstr) {
            $status_str .= chr(bindec($binstr));
        }
        return $status_str . $this->content;
    }

    public function setPayload(string $payload = ''): void
    {
        $this->status = 0;
        $this->content = '';
        if (strlen($payload) > 0) {
            $this->status = current(unpack('n', $payload));
        }
        if (strlen($payload) > 2) {
            $this->content = substr($payload, 2);
        }
    }
}
