<?php

/**
 * Copyright (C) 2014-2022 Textalk/Abicart and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/Textalk/websocket-php/master/COPYING
 */

namespace WebSocket;

use Throwable;

class ConnectionException extends Exception
{
    // General error codes
    public const TIMED_OUT = 1024;
    public const EOF = 1025;
    public const BAD_OPCODE = 1026;

    // Client error codes
    public const CLIENT_CONNECT_ERR = 1100;
    public const CLIENT_HANDSHAKE_ERR = 1101;

    // Server error codes
    public const SERVER_SOCKET_ERR = 1200;
    public const SERVER_ACCEPT_ERR = 1201;
    public const SERVER_HANDSHAKE_ERR = 1202;


    private $data;

    public function __construct(string $message, int $code = 0, array $data = [], Throwable $prev = null)
    {
        parent::__construct($message, $code, $prev);
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
