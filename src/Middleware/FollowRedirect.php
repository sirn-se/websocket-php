<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
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
use Stringable;
use WebSocket\{
    Configuration,
    Connection,
};
use WebSocket\Exception\{
    HandshakeException,
    ReconnectException,
};
use WebSocket\Trait\{
    ConfigurationTrait,
    StringableTrait,
};

/**
 * WebSocket\Middleware\CloseHandler class.
 * Handles close procedure.
 */
class FollowRedirect implements ProcessHttpIncomingInterface, Stringable
{
    use ConfigurationTrait;
    use StringableTrait;

    private const SCOPE = 'follow-redirect';

    private int $limit;
    private int $attempts = 1;

    public function __construct(int $limit = 10)
    {
        $this->limit = $limit;
        $this->initConfiguration();
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
            $context = [
                'scope' => self::SCOPE,
                'connection' => $connection->getIdentity(),
                'attempts' => $this->attempts,
                'limit' => $this->limit,
                'status' => $message->getStatusCode(),
                'location' => $locationHeader,
            ];
            if ($this->attempts > $this->limit) {
                $this->configuration->getLogger()->warning("[{scope}] Too many redirect attempts, giving up", $context);
                throw new HandshakeException('Too many redirect attempts, giving up', response: $message);
            }
            $this->attempts++;
            $this->configuration->getLogger()->info("[{scope}] Redirect {status} to {location}", $context);
            throw new ReconnectException(uri: new Uri($locationHeader));
        }
        return $message;
    }
}
