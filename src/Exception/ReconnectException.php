<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

use Phrity\Net\Uri;
use Psr\Http\Message\UriInterface;

/**
 * WebSocket\Exception\ReconnectException class.
 * Reconnect requested.
 */
class ReconnectException extends AbstractException implements ControlInterface
{
    protected static string $defaultMessage = 'Reconnect requested: {uri}';
    /** @var array{uri: UriInterface|null} $defaultContext */
    protected static array $defaultContext = ['uri' => null];

    public function getUri(): Uri|null
    {
        $uri = $this->getContext('uri');
        return $uri === null ? null : new Uri($uri);
    }
}
