<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Middleware;

use Closure;
use Psr\Http\Message\MessageInterface;
use Psr\Log\{
    LoggerInterface,
    LoggerAwareInterface,
};
use Stringable;
use WebSocket\Connection;
use WebSocket\Http\HttpHandler;
use WebSocket\Message\{
    Message,
    MessageHandler
};
use WebSocket\Trait\{
    LoggerAwareTrait,
    StringableTrait,
};

/**
 * WebSocket\Middleware\MiddlewareHandler class.
 * Middleware handling.
 */
class MiddlewareHandler implements LoggerAwareInterface, Stringable
{
    use LoggerAwareTrait;
    use StringableTrait;

    // Processor collections
    /** @var array<MiddlewareInterface> */
    private array $middlewares = [];
    /** @var array<ProcessIncomingInterface> */
    private array $incoming = [];
    /** @var array<ProcessOutgoingInterface> */
    private array $outgoing = [];
    /** @var array<ProcessHttpIncomingInterface> */
    private array $httpIncoming = [];
    /** @var array<ProcessHttpOutgoingInterface> */
    private array $httpOutgoing = [];
    /** @var array<ProcessTickInterface> */
    private array $tick = [];

    // Handlers
    private HttpHandler $httpHandler;
    private MessageHandler $messageHandler;

    /**
     * Create MiddlewareHandler.
     * @param MessageHandler $messageHandler
     * @param HttpHandler $httpHandler
     */
    public function __construct(MessageHandler $messageHandler, HttpHandler $httpHandler)
    {
        $this->messageHandler = $messageHandler;
        $this->httpHandler = $httpHandler;
        $this->initLogger();
    }

    /**
     * Set logger on MiddlewareHandler and all LoggerAware middlewares.
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
        foreach ($this->middlewares as $middleware) {
            $this->attachLogger($middleware);
        }
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
            $this->incoming[] = $middleware;
        }
        if ($middleware instanceof ProcessOutgoingInterface) {
            $this->logger->info("[middleware-handler] Added outgoing: {$middleware}");
            $this->outgoing[] = $middleware;
        }
        if ($middleware instanceof ProcessHttpIncomingInterface) {
            $this->logger->info("[middleware-handler] Added http incoming: {$middleware}");
            $this->httpIncoming[] = $middleware;
        }
        if ($middleware instanceof ProcessHttpOutgoingInterface) {
            $this->logger->info("[middleware-handler] Added http outgoing: {$middleware}");
            $this->httpOutgoing[] = $middleware;
        }
        if ($middleware instanceof ProcessTickInterface) {
            $this->logger->info("[middleware-handler] Added tick: {$middleware}");
            $this->tick[] = $middleware;
        }
        $this->attachLogger($middleware);
        $this->middlewares[] = $middleware;
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
     * @template T of Message
     * @param Connection $connection
     * @param T $message
     * @return T
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
     * @return MessageInterface
     */
    public function processHttpIncoming(Connection $connection): MessageInterface
    {
        $this->logger->info("[middleware-handler] Processing http incoming");
        $stack = new ProcessHttpStack($connection, $this->httpHandler, $this->httpIncoming);
        return $stack->handleHttpIncoming();
    }

    /**
     * Process middlewares for http requests.
     * @param Connection $connection
     * @param MessageInterface $message
     * @return MessageInterface
     */
    public function processHttpOutgoing(Connection $connection, MessageInterface $message): MessageInterface
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
