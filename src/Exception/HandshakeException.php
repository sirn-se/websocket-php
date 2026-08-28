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
    /**
     * @var array{
     *   headerName: string|null,
     *   headerValue: scalar|null,
     *   method: string|null,
     *   response: ResponseInterface|null,
     *   statusCode: int<100, 599>|null,
     * } $defaultContext
     */
    protected static array $defaultContext = [
        'headerName' => null,
        'headerValue' => null,
        'method' => null,
        'response' => null,
        'statusCode' => null,
    ];

    public function getResponse(): ResponseInterface
    {
        return $this->getContext('response');
    }
}
