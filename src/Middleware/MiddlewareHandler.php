<?php

/**
 * Copyright (C) 2014-2024 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Middleware;

use Closure;
use Psr\Log\{
    LoggerInterface,
    LoggerAwareInterface,
    NullLogger
};
use Stringable;
use WebSocket\Connection;
use WebSocket\Http\{
    HttpHandler,
    Message as HttpMessage
};
use WebSocket\Message\{
    Message,
    MessageHandler
};
use WebSocket\Trait\StringableTrait;

/**
 * WebSocket\Middleware\MiddlewareHandler class.
 * Middleware handling.
 */
class MiddlewareHandler implements LoggerAwareInterface, Stringable
{
    use StringableTrait;

    // Processor collections
    private $incoming = [];
    private $outgoing = [];
    private $httpIncoming = [];
    private $httpOutgoing = [];
    private $tick = [];

    // Handlers
    private $httpHandler;
    private $messageHandler;
    private $logger;

    /**
     * Create MiddlewareHandler.
     * @param MessageHandler $messageHandler
     * @param HttpHandler $httpHandler
     */
    public function __construct(MessageHandler $messageHandler, HttpHandler $httpHandler)
    {
        $this->messageHandler = $messageHandler;
        $this->httpHandler = $httpHandler;
        $this->setLogger(new NullLogger());
    }

    /**
     * Set logger on MiddlewareHandler and all LoggerAware middlewares.
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger): self
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
        return $this;
    }

    /**
     * Add a middleware.
     * @param MiddlewareInterface $middleware
     * @return $this
     */
    public function add(MiddlewareInterface $middleware): self
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
        if ($middleware instanceof ProcessHttpIncomingInterface) {
            $this->logger->info("[middleware-handler] Added http incoming: {$middleware}");
            if ($middleware instanceof LoggerAwareInterface) {
                $middleware->setLogger($this->logger);
            }
            $this->httpIncoming[] = $middleware;
        }
        if ($middleware instanceof ProcessHttpOutgoingInterface) {
            $this->logger->info("[middleware-handler] Added http outgoing: {$middleware}");
            if ($middleware instanceof LoggerAwareInterface) {
                $middleware->setLogger($this->logger);
            }
            $this->httpOutgoing[] = $middleware;
        }
        if ($middleware instanceof ProcessTickInterface) {
            $this->logger->info("[middleware-handler] Added tick: {$middleware}");
            if ($middleware instanceof LoggerAwareInterface) {
                $middleware->setLogger($this->logger);
            }
            $this->tick[] = $middleware;
        }
        return $this;
    }

    /**
     * Process middlewares for incoming messages.
     * @param Connection $connection
     * @return Message
     */
    public function processIncoming(Connection $connection): Message
    {
        $this->logger->info("[middleware-handler] Processing incoming");
        $stack = new ProcessStack($connection, $this->messageHandler, $this->incoming);
        return $stack->handleIncoming();
    }

    /**
     * Process middlewares for outgoing messages.
     * @param Connection $connection
     * @param Message $message
     * @return Message
     */
    public function processOutgoing(Connection $connection, Message $message): Message
    {
        $this->logger->info("[middleware-handler] Processing outgoing");
        $stack = new ProcessStack($connection, $this->messageHandler, $this->outgoing);
        return $stack->handleOutgoing($message);
    }

    /**
     * Process middlewares for http requests.
     * @param Connection $connection
     * @return HttpMessage
     */
    public function processHttpIncoming(Connection $connection): HttpMessage
    {
        $this->logger->info("[middleware-handler] Processing http incoming");
        $stack = new ProcessHttpStack($connection, $this->httpHandler, $this->httpIncoming);
        return $stack->handleHttpIncoming();
    }

    /**
     * Process middlewares for http requests.
     * @param Connection $connection
     * @param HttpMessage $message
     * @return HttpMessage
     */
    public function processHttpOutgoing(Connection $connection, HttpMessage $message): HttpMessage
    {
        $this->logger->info("[middleware-handler] Processing http outgoing");
        $stack = new ProcessHttpStack($connection, $this->httpHandler, $this->httpOutgoing);
        return $stack->handleHttpOutgoing($message);
    }

    /**
     * Process middlewares for tick.
     * @param Connection $connection
     */
    public function processTick(Connection $connection): void
    {
        $this->logger->info("[middleware-handler] Processing tick");
        $stack = new ProcessTickStack($connection, $this->tick);
        $stack->handleTick();
    }
}
