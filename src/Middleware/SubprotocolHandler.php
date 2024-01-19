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
use WebSocket\Http\Message;
use WebSocket\Trait\StringableTrait;

/**
 * WebSocket\Middleware\CloseHandler class.
 * Handles close procedure.
 */
class SubprotocolHandler implements LoggerAwareInterface, ProcessHttpOutgoingInterface, ProcessHttpIncomingInterface
{
    use LoggerAwareTrait;
    use StringableTrait;

    private $subprotocols;
    private $selected = null;

    public function __construct(array $subprotocols)
    {
        $this->subprotocols = $subprotocols;
    }

    public function processHttpOutgoing(ProcessHttpStack $stack, Connection $connection, Message $message): Message
    {
        if ($message instanceof \WebSocket\Http\Request) {
            // Outgoing requests on Client
            foreach ($this->subprotocols as $subprotocol) {
                $message = $message->withAddedHeader('Sec-WebSocket-Protocol', $subprotocol);
            }
        } elseif ($message instanceof \WebSocket\Http\Response) {
            // Outgoing Response
            if ($this->selected) {
                $message = $message->withHeader('Sec-WebSocket-Protocol', $this->selected);
            }
        }

        return $stack->handleHttpOutgoing($message);
    }

    public function processHttpIncoming(ProcessHttpStack $stack, Connection $connection): Message
    {
        $this->selected = null;
        $message = $stack->handleHttpIncoming();

        if ($message instanceof \WebSocket\Http\ServerRequest) {
            // Incoming requests on Server
            foreach ($message->getHeader('Sec-WebSocket-Protocol') as $subprotocol) {
                if (in_array($subprotocol, $this->subprotocols)) {
                    $this->selected = $subprotocol;
                    return $message;
                }
            }
        }

        return $message;
    }
}
