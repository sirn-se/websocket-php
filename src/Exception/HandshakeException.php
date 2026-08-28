<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

use Psr\Http\Message\ResponseInterface;

/**
 * WebSocket\Exception\HandshakeException class.
 * Exception during handshake
 */
class HandshakeException extends AbstractException implements ConnectionLevelInterface
{
    protected static string $defaultMessage = 'Handshake failed';

    private ResponseInterface $response;

    public function __construct(
        string $message,
        ResponseInterface $response,
    ) {
        $this->response = $response;
        parent::__construct($message);
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
