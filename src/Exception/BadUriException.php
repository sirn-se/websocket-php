<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

/**
 * WebSocket\Exception\BadUriException class.
 * Thrown when invalid URI is provided.
 */
class BadUriException extends AbstractException implements HandlerLevelInterface
{
    protected static string $defaultMessage = 'Bad URI';
}
