<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Exception;

use WebSocket\Http\Response;

/**
 * WebSocket\Exception\HandshakeException class.
 * Exception during handshake
 */
class HandshakeException extends Exception implements ConnectionLevelInterface
{
    private $response;

    public function __construct(string $message, Response $response)
    {
        parent::__construct($message);
        $this->response = $response;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
