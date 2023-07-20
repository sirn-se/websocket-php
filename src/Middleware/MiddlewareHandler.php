<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Middleware;

use Closure;
use Psr\Log\{
    LoggerInterface,
    LoggerAwareInterface,
    NullLogger
};
use WebSocket\Connection;
use WebSocket\Message\Message;

/**
 * WebSocket\Middleware\MiddlewareHandler class.
 * Middleware handling.
 */
class MiddlewareHandler implements LoggerAwareInterface
{
    private $incoming = [];
    private $outgoing = [];

    public function __construct()
    {
        $this->setLogger(new NullLogger());
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
        foreach ($this->incoming as $middleware) {
            if ($middleware instanceof LoggerAwareInterface) {
                $middleware->setLogger($this->logger);
            }
        }
        foreach ($this->outgoing as $middleware) {
            if ($middleware instanceof LoggerAwareInterface) {
                $middleware->setLogger($this->logger);
            }
        }
    }

    public function add(MiddlewareInterface $middleware): void
    {
        if ($middleware instanceof ProcessIncomingInterface) {
            $this->logger->info("[middleware-handler] Added incoming: {$middleware}");
            if ($middleware instanceof LoggerAwareInterface) {
                $middleware->setLogger($this->logger);
            }
            $this->incoming[] = $middleware;
        }
        if ($middleware instanceof ProcessOutgoingInterface) {
            $this->logger->info("[middleware-handler] Added outgoing: {$middleware}");
            if ($middleware instanceof LoggerAwareInterface) {
                $middleware->setLogger($this->logger);
            }
            $this->outgoing[] = $middleware;
        }
    }

    public function processIncoming(Connection $connection, Closure $cb): Message
    {
        $this->logger->info("[middleware-handler] Processing incoming");
        $stack = new ProcessStack($connection, $this->incoming, $cb);
        return $stack->handleIncoming();
    }

    public function processOutgoing(Connection $connection, Message $message, Closure $cb): Message
    {
        $this->logger->info("[middleware-handler] Processing outgoing");
        $stack = new ProcessStack($connection, $this->outgoing, $cb);
        return $stack->handleOutgoing($message);
    }
}
