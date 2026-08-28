<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

use Phrity\Net\Uri;

/**
 * WebSocket\Exception\ReconnectException class.
 * Reconnect requested.
 */
class ReconnectException extends AbstractException implements ControlInterface
{
    protected static string $defaultMessage = 'Reconnect requested: {uri}';

    private Uri|null $uri;

    public function __construct(Uri|null $uri = null, string|null $message = null)
    {
        $this->uri = $uri;
        parent::__construct($message, context: ['uri' => $uri]);
    }

    public function getUri(): Uri|null
    {
        return $this->uri;
    }
}
