<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

/**
 * WebSocket\Exception\BadUriException class.
 * Thrown when invalid URI is provided.
 */
class BadUriException extends AbstractHandlerException
{
    protected static string $defaultMessage = 'Bad URI';
}
