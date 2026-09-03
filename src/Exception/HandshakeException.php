<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface,
};

/**
 * WebSocket\Exception\HandshakeException class.
 * Exception during handshake
 */
class HandshakeException extends AbstractException implements ConnectionLevelInterface
{
    protected static string $defaultMessage = 'Handshake failed';
    protected static array $defaultContext = [
        'request' => null,
        'response' => null,
    ];

    public function getResponse(): ResponseInterface|null
    {
        return $this->getContext('response');
    }
}
