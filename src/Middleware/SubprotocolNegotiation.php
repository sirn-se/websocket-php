<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Middleware;

use Psr\Log\{
    LoggerAwareInterface,
    LoggerAwareTrait
};
use WebSocket\Connection;
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
class SubprotocolNegotiation implements LoggerAwareInterface, ProcessHttpOutgoingInterface, ProcessHttpIncomingInterface
{
    use LoggerAwareTrait;
    use StringableTrait;

    private $subprotocols;

    public function __construct(array $subprotocols)
    {
        $this->subprotocols = $subprotocols;
    }

    public function processHttpOutgoing(ProcessHttpStack $stack, Connection $connection, Message $message): Message
    {
        if ($message instanceof Request) {
            // Outgoing requests on Client
            foreach ($this->subprotocols as $subprotocol) {
                $message = $message->withAddedHeader('Sec-WebSocket-Protocol', $subprotocol);
            }
        } elseif ($message instanceof Response) {
            // Outgoing Response from Server
            if ($selected = $connection->getMeta('subprotocolNegotiation.selected')) {
                $message = $message->withHeader('Sec-WebSocket-Protocol', $selected);
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
            foreach ($message->getHeader('Sec-WebSocket-Protocol') as $subprotocol) {
                if (in_array($subprotocol, $this->subprotocols)) {
                    $connection->setMeta('subprotocolNegotiation.selected', $subprotocol);
                    $this->logger->info("Selected subprotocol: {$subprotocol}");
                    return $message;
                }
            }
        }
        return $message;
    }
}
