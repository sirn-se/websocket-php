<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

use Psr\Http\Message\UriInterface;

/**
 * WebSocket\Exception\ClientException class.
 * Fatal exception during server start
 */
class ClientException extends AbstractException implements HandlerLevelInterface
{
    protected static string $defaultMessage = 'Client failed on {uri}';
    /** @var array{uri: UriInterface|null} $defaultContext */
    protected static array $defaultContext = ['uri' => null];
}
