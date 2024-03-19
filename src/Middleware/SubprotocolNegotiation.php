<?php

/**
 * Copyright (C) 2014-2024 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Middleware;

use Psr\Log\{
    LoggerAwareInterface,
    LoggerAwareTrait
};
use Stringable;
use WebSocket\Connection;
use WebSocket\Exception\HandshakeException;
use WebSocket\Http\{
    Message,
    Request,
    Response,
    ServerRequest,
};
use WebSocket\Trait\StringableTrait;

/**
 * WebSocket\Middleware\CloseHandler class.
 * Handles close procedure.
 */
class SubprotocolNegotiation implements
    LoggerAwareInterface,
    ProcessHttpOutgoingInterface,
    ProcessHttpIncomingInterface,
    Stringable
{
    use LoggerAwareTrait;
    use StringableTrait;

    private $subprotocols;
    private $require;

    public function __construct(array $subprotocols, bool $require = false)
    {
        $this->subprotocols = $subprotocols;
        $this->require = $require;
    }

    public function processHttpOutgoing(ProcessHttpStack $stack, Connection $connection, Message $message): Message
    {
        if ($message instanceof Request) {
            // Outgoing requests on Client
            foreach ($this->subprotocols as $subprotocol) {
                $message = $message->withAddedHeader('Sec-WebSocket-Protocol', $subprotocol);
            }
            if ($supported = implode(', ', $this->subprotocols)) {
                $this->logger->debug("[subprotocol-negotiation] Requested subprotocols: {$supported}");
            }
        } elseif ($message instanceof Response) {
            // Outgoing Response on Server
            if ($selected = $connection->getMeta('subprotocolNegotiation.selected')) {
                $message = $message->withHeader('Sec-WebSocket-Protocol', $selected);
                $this->logger->info("[subprotocol-negotiation] Selected subprotocol: {$selected}");
            } elseif ($this->require) {
                // No matching subprotocol, fail handshake
                $message = $message->withStatus(426);
            }
        }
        return $stack->handleHttpOutgoing($message);
    }

    public function processHttpIncoming(ProcessHttpStack $stack, Connection $connection): Message
    {
        $connection->setMeta('subprotocolNegotiation.selected', null);
        $message = $stack->handleHttpIncoming();

        if ($message instanceof ServerRequest) {
            // Incoming requests on Server
            if ($requested = $message->getHeaderLine('Sec-WebSocket-Protocol')) {
                $this->logger->debug("[subprotocol-negotiation] Requested subprotocols: {$requested}");
            }
            if ($supported = implode(', ', $this->subprotocols)) {
                $this->logger->debug("[subprotocol-negotiation] Supported subprotocols: {$supported}");
            }
            foreach ($message->getHeader('Sec-WebSocket-Protocol') as $subprotocol) {
                if (in_array($subprotocol, $this->subprotocols)) {
                    $connection->setMeta('subprotocolNegotiation.selected', $subprotocol);
                    return $message;
                }
            }
        } elseif ($message instanceof Response) {
            // Incoming Response on Client
            if ($selected = $message->getHeaderLine('Sec-WebSocket-Protocol')) {
                $connection->setMeta('subprotocolNegotiation.selected', $selected);
                $this->logger->info("[subprotocol-negotiation] Selected subprotocol: {$selected}");
            } elseif ($this->require) {
                // No matching subprotocol, close and fail
                $connection->close();
                throw new HandshakeException("Could not resolve subprotocol.", $message);
            }
        }
        return $message;
    }
}
