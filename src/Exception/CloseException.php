<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Exception;

/**
 * WebSocket\Exception\CloseException class.
 * Connection should close
 */
class CloseException extends Exception
{
    protected $status;
    protected $content;

    public function __construct(int|null $status = null, string $content = '')
    {
        $this->status = $status;
        parent::__construct($content);
    }

    public function getCloseStatus(): int|null
    {
        return $this->status;
    }
}
