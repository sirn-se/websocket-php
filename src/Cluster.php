<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket;

use Phrity\Net\{
    Context,
    StreamFactory,
};
use Psr\Log\{
    LoggerAwareInterface,
    LoggerInterface,
};
use Stringable;
use Throwable;
use WebSocket\Exception\{
    ServerException
};
use WebSocket\Http\{
    DefaultHttpFactory,
    Response,
    ServerRequest,
};
use WebSocket\Trait\{
    ConfigurationTrait,
    StringableTrait
};
use WebSocket\Runtime\Watcher;

/**
 * WebSocket\Server class.
 * Entry class for WebSocket server.
 */
class Cluster implements LoggerAwareInterface, Stringable
{
    use ConfigurationTrait;
    use StringableTrait;

    private StreamFactory $streamFactory;
    private Watcher $watcher;

    private array $services = [];


    /* ---------- Magic methods ------------------------------------------------------------------------------------ */

    /**
     * @param StreamFactory|null $streamFactory
     * @param Watcher|null $watcher
     */
    public function __construct(
        StreamFactory|null $streamFactory = null,
        Watcher|null $watcher = null,
        Configuration|null $configuration = null,
    ) {
        $this->streamFactory = $streamFactory ?? new StreamFactory();
        $this->watcher = $watcher ?? new Watcher($this->streamFactory->createStreamCollection());
        $this->initConfiguration($configuration);
   }

    /**
     * Get string representation of instance.
     * @return string String representation
     */
    public function __toString(): string
    {
        return $this->stringable('');
    }



    public function createServer(int $port = 80, bool $ssl = false, string|null $name = null): Server
    {
        $name = $name ?? "server:{$port}";
        if (array_key_exists($name, $this->services)) {
            throw new ServerException("Server {$name} already attached.");
        }
        $server = new Server(
            port: $port,
            ssl: $ssl,
            streamFactory: $this->streamFactory,
            watcher: $this->watcher,
            configuration: $this->configuration,
        );
        $this->services[$name] = $server;
        return $server;
    }

    /**
     * Set logger.
     * @param LoggerInterface $logger Logger implementation
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->configuration->setLogger($logger);
    }

    public function start()
    {
        foreach ($this->services as $service) {
            $service->start();
        }
    }
}
