<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

/**
 * WebSocket\Exception\MessageEncodingException class.
 * Message content could not be encoded/decoded.
 */
class MessageEncodingException extends AbstractException implements MessageLevelInterface
{
    protected static string $defaultMessage = 'Message encoding error';
}
