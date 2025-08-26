<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Middleware;

use Phrity\Net\Uri;
use Psr\Http\Message\{
    MessageInterface,
    ResponseInterface,
};
use Psr\Log\LoggerAwareInterface;
use Stringable;
use WebSocket\Connection;
use WebSocket\Exception\{
    HandshakeException,
    ReconnectException,
};
use WebSocket\Trait\{
    LoggerAwareTrait,
    StringableTrait,
};

/**
 * WebSocket\Middleware\CloseHandler class.
 * Handles close procedure.
 */
class FollowRedirect implements LoggerAwareInterface, ProcessHttpIncomingInterface, Stringable
{
    use LoggerAwareTrait;
    use StringableTrait;

    private int $limit;
    private int $attempts = 1;

    public function __construct(int $limit = 10)
    {
        $this->limit = $limit;
        $this->initLogger();
    }

    /**
     * @throws HandshakeException
     * @throws ReconnectException
     */
    public function processHttpIncoming(ProcessHttpStack $stack, Connection $connection): MessageInterface
    {
        $message = $stack->handleHttpIncoming();
        if (
            $message instanceof ResponseInterface
            && $message->getStatusCode() >= 300
            && $message->getStatusCode() < 400
            && $locationHeader = $message->getHeaderLine('Location')
        ) {
            $note = "{$this->attempts} of {$this->limit} redirect attempts";
            if ($this->attempts > $this->limit) {
                $this->logger->debug("[follow-redirect] Too many redirect attempts, giving up");
                throw new HandshakeException("Too many redirect attempts, giving up", $message);
            }
            $this->attempts++;
            $this->logger->debug("[follow-redirect] {$message->getStatusCode()} {$locationHeader} ($note)");
            throw new ReconnectException(new Uri($locationHeader));
        }
        return $message;
    }
}
