<?php

/**
 * Copyright (C) 2014-2024 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Middleware;

use Phrity\Net\Uri;
use Psr\Log\{
    LoggerAwareInterface,
    LoggerAwareTrait
};
use Stringable;
use WebSocket\Connection;
use WebSocket\Exception\{
    HandshakeException,
    ReconnectException
};
use WebSocket\Http\{
    Message,
    Response
};
use WebSocket\Trait\StringableTrait;

/**
 * WebSocket\Middleware\CloseHandler class.
 * Handles close procedure.
 */
class FollowRedirect implements LoggerAwareInterface, ProcessHttpIncomingInterface, Stringable
{
    use LoggerAwareTrait;
    use StringableTrait;

    private $limit;
    private $attempts = 0;

    public function __construct(int $limit = 10)
    {
        $this->limit = $limit;
    }

    public function processHttpIncoming(ProcessHttpStack $stack, Connection $connection): Message
    {
        $message = $stack->handleHttpIncoming();
        if (
            $message instanceof Response
            && $message->getStatusCode() >= 300
            && $message->getStatusCode() < 400
            && $locationHeader = $message->getHeaderLine('Location')
        ) {
            $note = "{$this->attempts} of {$this->limit} redirect attempts";
            if ($this->attempts >= $this->limit) {
                $this->logger->debug("[follow-redirect] Too many redirect attempts, giving up");
                throw new HandshakeException("{$note}, giving up", $message);
            }
            $this->attempts++;
            $this->logger->debug("[follow-redirect] {$message->getStatusCode()} {$locationHeader} ($note)");
            throw new ReconnectException(new Uri($locationHeader));
        }
        $this->logger->debug("Exp {$message->getStatusCode()} ");
        return $message;
    }
}
