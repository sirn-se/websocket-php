<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

use Psr\Http\Message\ResponseInterface;
use WebSocket\Connection;
use Throwable;

/**
 * WebSocket\Exception\HandshakeException class.
 * Exception during handshake
 */
class HandshakeException extends AbstractConnectionException
{
    protected static string $defaultMessage = 'Handshake failed';

    private ResponseInterface $response;

    public function __construct(
        Connection $connection,
        ResponseInterface $response,
        string|null $message = null,
    ) {
        $this->response = $response;
        parent::__construct($connection, $message);
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
