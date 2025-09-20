<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Middleware;

use Closure;
use Psr\Http\Message\MessageInterface;
use Stringable;
use WebSocket\Connection;
use WebSocket\Configuration;
use WebSocket\Http\HttpHandler;
use WebSocket\Message\{
    Message,
    MessageHandler
};
use WebSocket\Trait\{
    ConfigurationTrait,
    StringableTrait,
};

/**
 * WebSocket\Middleware\MiddlewareHandler class.
 * Middleware handling.
 */
class MiddlewareHandler implements Stringable
{
    use ConfigurationTrait;
    use StringableTrait;

    // Processor collections
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
    public function __construct(
        MessageHandler $messageHandler,
        HttpHandler $httpHandler,
        Configuration|null $configuration = null
    ) {
        $this->messageHandler = $messageHandler;
        $this->httpHandler = $httpHandler;
        $this->initConfiguration($configuration);
    }

    /**
     * Add a middleware.
     * @param MiddlewareInterface $middleware
     * @return $this
     */
    public function add(MiddlewareInterface $middleware): self
    {
        if ($middleware instanceof ProcessIncomingInterface) {
            $this->configuration->getLogger()->info("[middleware-handler] Added incoming: {$middleware}");
            $this->incoming[] = $middleware;
        }
        if ($middleware instanceof ProcessOutgoingInterface) {
            $this->configuration->getLogger()->info("[middleware-handler] Added outgoing: {$middleware}");
            $this->outgoing[] = $middleware;
        }
        if ($middleware instanceof ProcessHttpIncomingInterface) {
            $this->configuration->getLogger()->info("[middleware-handler] Added http incoming: {$middleware}");
            $this->httpIncoming[] = $middleware;
        }
        if ($middleware instanceof ProcessHttpOutgoingInterface) {
            $this->configuration->getLogger()->info("[middleware-handler] Added http outgoing: {$middleware}");
            $this->httpOutgoing[] = $middleware;
        }
        if ($middleware instanceof ProcessTickInterface) {
            $this->configuration->getLogger()->info("[middleware-handler] Added tick: {$middleware}");
            $this->tick[] = $middleware;
        }
        $middleware->setConfiguration($this->configuration);
        return $this;
    }

    /**
     * Process middlewares for incoming messages.
     * @param Connection $connection
     * @return Message
     */
    public function processIncoming(Connection $connection): Message
    {
        $this->configuration->getLogger()->info("[middleware-handler] Processing incoming");
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
        $this->configuration->getLogger()->info("[middleware-handler] Processing outgoing");
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
        $this->configuration->getLogger()->info("[middleware-handler] Processing http incoming");
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
        $this->configuration->getLogger()->info("[middleware-handler] Processing http outgoing");
        $stack = new ProcessHttpStack($connection, $this->httpHandler, $this->httpOutgoing);
        return $stack->handleHttpOutgoing($message);
    }

    /**
     * Process middlewares for tick.
     * @param Connection $connection
     */
    public function processTick(Connection $connection): void
    {
        $this->configuration->getLogger()->info("[middleware-handler] Processing tick");
        $stack = new ProcessTickStack($connection, $this->tick);
        $stack->handleTick();
    }
}
