<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket;

use InvalidArgumentException;
use Phrity\Http\HttpFactory;
use Phrity\Net\{
    Context,
    SocketServer,
    SocketStream,
    StreamCollection,
    StreamException,
    StreamFactory,
    Uri
};
use Psr\Log\{
    LoggerAwareInterface,
    LoggerInterface,
};
use Stringable;
use Throwable;
use WebSocket\Exception\{
    CloseException,
    ConnectionFailureException,
    ConnectionLevelInterface,
    ExceptionInterface,
    HandshakeException,
    MessageLevelInterface,
    ServerException
};
use WebSocket\Http\{
    DefaultHttpFactory,
    Response,
    ServerRequest,
};
use WebSocket\Message\Message;
use WebSocket\Middleware\MiddlewareInterface;
use WebSocket\Trait\{
    ConfigurationTrait,
    ListenerTrait,
    SendMethodsTrait,
    StringableTrait
};
use WebSocket\Runtime\Watcher;

/**
 * WebSocket\Server class.
 * Entry class for WebSocket server.
 */
class Server implements LoggerAwareInterface, Stringable
{
    use ConfigurationTrait;
    /** @use ListenerTrait<Server> */
    use ListenerTrait;
    use SendMethodsTrait;
    use StringableTrait;

    // Settings
    private int $port;
    private string $scheme;

    // Internal resources
    private SocketServer|null $server = null;
    private bool $running = false;
    /** @var array<Connection> $connections */
    private array $connections = [];
    /** @var array<MiddlewareInterface> $middlewares */
    private array $middlewares = [];
    private bool $allowConnections = false;

    private StreamFactory $streamFactory;
    private HttpFactory $httpFactory;
    private Watcher $watcher;
    private string $identity;


    /* ---------- Magic methods ------------------------------------------------------------------------------------ */

    /**
     * @param int<0, 65535> $port Socket port to listen to
     * @param bool $ssl If SSL should be used
     * @param StreamFactory|null $streamFactory
     * @param HttpFactory|null $httpFactory
     * @param Watcher|null $watcher
     * @param Configuration|null $configuration
     * @throws InvalidArgumentException If invalid port provided
     */
    public function __construct(
        int $port = 80,
        bool $ssl = false,
        StreamFactory|null $streamFactory = null,
        HttpFactory|null $httpFactory = null,
        Watcher|null $watcher = null,
        Configuration|null $configuration = null,
    ) {
        if ($port < 0 || $port > 65535) {
            throw new InvalidArgumentException("Invalid port '{$port}' provided");
        }
        $this->port = $port;
        $this->scheme = $ssl ? 'ssl' : 'tcp';
        $this->streamFactory = $streamFactory ?? new StreamFactory();
        $this->httpFactory = $httpFactory ?? new DefaultHttpFactory();
        $this->watcher = $watcher ?? new Watcher($this->streamFactory->createStreamCollection());
        $this->initConfiguration($configuration);
        $this->identity = "server:{$port}";
    }

    /**
     * Get string representation of instance.
     * @return string String representation
     */
    public function __toString(): string
    {
        return $this->stringable('%s', $this->server ? "{$this->scheme}://0.0.0.0:{$this->port}" : 'closed');
    }


    /* ---------- Configuration ------------------------------------------------------------------------------------ */

    /**
     * Set stream factory to use.
     * @param StreamFactory $streamFactory
     * @return self
     */
    public function setStreamFactory(StreamFactory $streamFactory): self
    {
        $this->streamFactory = $streamFactory;
        return $this;
    }

    /**
     * Set HTTP factory to use.
     * @param HttpFactory $httpFactory
     * @return self
     */
    public function setHttpFactory(HttpFactory $httpFactory): self
    {
        $this->httpFactory = $httpFactory;
        return $this;
    }

    /**
     * Set logger.
     * @param LoggerInterface $logger Logger implementation
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->configuration->setLogger($logger);
    }

    /**
     * Set timeout.
     * @param int<0, max>|float $timeout Timeout in seconds
     * @return self
     * @throws InvalidArgumentException If invalid timeout provided
     */
    public function setTimeout(int|float $timeout): self
    {
        $this->configuration->setTimeout($timeout);
        return $this;
    }

    /**
     * Get timeout.
     * @return int<0, max>|float Timeout in seconds
     */
    public function getTimeout(): int|float
    {
        return $this->configuration->getTimeout();
    }

    /**
     * Set frame size.
     * @param int<1, max> $frameSize Max frame payload size in bytes
     * @return self
     */
    public function setFrameSize(int $frameSize): self
    {
        $this->configuration->setFrameSize($frameSize);
        return $this;
    }

    /**
     * Get frame size.
     * @return int Frame size in bytes
     */
    public function getFrameSize(): int
    {
        return $this->configuration->getFrameSize();
    }

    /**
     * Get socket port number.
     * @return int port
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * Get connection scheme.
     * @return string scheme
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * Get connection scheme.
     * @return bool SSL mode
     */
    public function isSsl(): bool
    {
        return $this->scheme === 'ssl';
    }

    /**
     * Number of currently connected clients.
     * @return int Connection count
     */
    public function getConnectionCount(): int
    {
        return count($this->connections);
    }

    /**
     * Get currently connected clients.
     * @return array<Connection> Connections
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * Get currently readable clients.
     * @return array<Connection> Connections
     */
    public function getReadableConnections(): array
    {
        return array_filter($this->connections, function (Connection $connection) {
            return $connection->isReadable();
        });
    }

    /**
     * Get currently writable clients.
     * @return array<Connection> Connections
     */
    public function getWritableConnections(): array
    {
        return array_filter($this->connections, function (Connection $connection) {
            return $connection->isWritable();
        });
    }

    /**
     * Set stream context.
     * @param Context $context Context or options as array
     * @see https://www.php.net/manual/en/context.php
     * @return self
     */
    public function setContext(Context $context): self
    {
        $this->configuration->setContext($context);
        return $this;
    }

    /**
     * Get current stream context.
     * @return Context
     */
    public function getContext(): Context
    {
        return $this->configuration->getContext();
    }

    /**
     * Add a middleware.
     * @param MiddlewareInterface $middleware
     * @return self
     */
    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        foreach ($this->connections as $connection) {
            $connection->addMiddleware($middleware);
        }
        return $this;
    }

    /**
     * Set maximum number of connections allowed, null means unlimited.
     * @param int<1, max>|null $maxConnections
     * @return self
     */
    public function setMaxConnections(int|null $maxConnections): self
    {
        $this->configuration->setMaxConnections($maxConnections);
        return $this;
    }


    /* ---------- Messaging operations ----------------------------------------------------------------------------- */

    /**
     * Send message (broadcast to all connected clients).
     * @template T of Message
     * @param T $message
     * @return T
     */
    public function send(Message $message): Message
    {
        foreach ($this->connections as $connection) {
            if ($connection->isWritable()) {
                $connection->send($message);
            }
        }
        return $message;
    }


    /* ---------- Listener operations ------------------------------------------------------------------------------ */

    /**
     * Start server listener.
     */
    public function start(int|float|null $timeout = null): void
    {
        // Check if running
        if ($this->running) {
            $this->configuration->getLogger()->warning("[{$this->identity}] Server is already running");
            return;
        }
        $this->beforeStart();
        $this->running = true;
        $this->configuration->getLogger()->info("[{$this->identity}] Server is running");

        // Run handler
        while ($this->running) {
            try {
                $this->beforeWatch();
                if ($this->watcher->isEmpty()) {
                    $this->stop();
                    return;
                }
                $this->watcher->watch($timeout ?? $this->configuration->getTimeout());
                $this->afterWatch();
            } catch (ExceptionInterface $e) {
                // Low-level error
                $this->configuration->getLogger()->error("[{$this->identity}] {$e->getMessage()}", ['exception' => $e]);
                $this->dispatch('error', [$this, null, $e]);
            } catch (Throwable $e) {
                // Crash it
                $this->configuration->getLogger()->error("[{$this->identity}] {$e->getMessage()}", ['exception' => $e]);
                $this->disconnect();
                throw $e;
            }
            gc_collect_cycles(); // Collect garbage
        }
    }

    /**
     * Stop server listener (resumable).
     */
    public function stop(): void
    {
        $this->running = false;
        $this->configuration->getLogger()->info("[{$this->identity}] Server is stopped");
    }

    /**
     * If server is running (accepting connections and messages).
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->running;
    }

    private function selectHandler(string $key, SocketStream $stream): void
    {
        try {
            // Read from connection
            $connection = $this->connections[$key];
            $message = $connection->pullMessage();
            $this->dispatch($message->getOpcode(), [$this, $connection, $message]);
        } catch (MessageLevelInterface $e) {
            // Error, but keep connection open
            $this->configuration->getLogger()->error("[{$this->identity}] {$e->getMessage()}", ['exception' => $e]);
            $this->dispatch('error', [$this, $connection, $e]);
        } catch (ConnectionLevelInterface $e) {
            // Error, disconnect connection
            if ($connection) {
                $this->watcher->detach($key);
                unset($this->connections[$key]);
                $connection->disconnect();
            }
            $this->configuration->getLogger()->error("[{$this->identity}] {$e->getMessage()}", ['exception' => $e]);
            $this->dispatch('error', [$this, $connection, $e]);
        } catch (CloseException $e) {
            // Should close
            if ($connection) {
                $connection->close($e->getCloseStatus(), $e->getMessage());
            }
            $this->configuration->getLogger()->error("[{$this->identity}] {$e->getMessage()}", ['exception' => $e]);
            $this->dispatch('error', [$this, $connection, $e]);
        }
    }


    /* ---------- Connection management ---------------------------------------------------------------------------- */

    /**
     * Orderly shutdown of server.
     * @param int $closeStatus Default is 1001 "Going away"
     */
    public function shutdown(int $closeStatus = 1001): void
    {
        $this->configuration->getLogger()->info('[{$this->identity}] Shutting down');
        if ($this->getConnectionCount() == 0) {
            $this->disconnect();
            return;
        }
        // Store and reset settings, lock new connections, reset listeners
        $this->allowConnections = false;
        $listeners = $this->listeners;
        $this->listeners = [];
        // Track disconnects
        $this->onDisconnect(function () use ($listeners) {
            if ($this->getConnectionCount() > 0) {
                return;
            }
            $this->disconnect();
            // Restore settings
            $this->listeners = $listeners;
        });
        // Close all current connections, listen to acks
        $this->close($closeStatus);
        $this->start();
    }

    /**
     * Disconnect all connections and stop server.
     */
    public function disconnect(): void
    {
        $this->running = false;
        $this->watcher->detach($this->identity);
        foreach ($this->connections as $connection) {
            $connection->disconnect();
            $this->dispatch('disconnect', [$this, $connection]);
        }
        $this->connections = [];
        if ($this->server) {
            $this->server->close();
        }
        $this->server = null;
        $this->configuration->getLogger()->info("[{$this->identity}] Server disconnected");
    }


    /* ---------- Internal helper methods -------------------------------------------------------------------------- */

    // Create socket server
    protected function createSocketServer(): void
    {
        try {
            $uri = new Uri("{$this->scheme}://0.0.0.0:{$this->port}");
            $this->server = $this->streamFactory->createSocketServer($uri, $this->configuration->getContext());
            /** @throws StreamException */
            $this->watcher->attach($this->identity, $this->server, function (string $key, SocketServer $socket) {
                $this->acceptHandler($socket);
            });
            $this->allowConnections = true;
            $this->configuration->getLogger()->info("[{$this->identity}] Starting server on {$uri}.");
        } catch (StreamException $e) {
            $error = "Server failed to start: {$e->getMessage()}";
            $this->configuration->getLogger()->error("[{$this->identity}] {$error}");
            throw new ServerException($error);
        } catch (Throwable $e) {
            $error = "Server error: {$e->getMessage()}";
            $this->configuration->getLogger()->error("[{$this->identity}] {$error}");
            throw $e;
        }
    }

    // Accept connection on socket server
    protected function acceptHandler(SocketServer $socket): void
    {
        $maxConnections = $this->configuration->getMaxConnections();
        if (!is_null($maxConnections) && $this->getConnectionCount() >= $maxConnections) {
            $this->configuration->getLogger()->warning(
                "[{$this->identity}] Denied connection, reached max {$maxConnections}"
            );
            return;
        }
        if (!$this->allowConnections) {
            $this->configuration->getLogger()->warning("[{$this->identity}] Denied connection, shutting down");
            return;
        }
        try {
            /** @var SocketStream $stream */
            $stream = $socket->accept();
            $name = $stream->getRemoteName() ?? 'unknown';
            $this->watcher->attach($name, $stream, function (string $key, SocketStream $stream) {
                $this->selectHandler($key, $stream);
            });
            $connection = new Connection(
                $stream,
                false,
                true,
                $this->isSsl(),
                $this->httpFactory,
                $this->configuration
            );
        } catch (StreamException $e) {
            throw new ConnectionFailureException("Server failed to accept: {$e->getMessage()}");
        }
        try {
            foreach ($this->middlewares as $middleware) {
                $connection->addMiddleware($middleware);
            }
            /** @throws StreamException */
            $request = $this->performHandshake($connection);
            $this->connections[$name] = $connection;
            $this->configuration->getLogger()->info("[{$this->identity}] Accepted connection from {$name}.");
            $this->dispatch('handshake', [
                $this,
                $connection,
                $connection->getHandshakeRequest(),
                $connection->getHandshakeResponse(),
            ]);
            $this->dispatch('connect', [$this, $connection, $request]);
        } catch (ExceptionInterface | StreamException $e) {
            $connection->disconnect();
            throw new ConnectionFailureException("Server failed to accept: {$e->getMessage()}");
        }
    }

    public function beforeStart(): void
    {
        if (empty($this->server)) {
            $this->createSocketServer();
        }
    }

    public function beforeWatch(): void
    {
        $this->detachUnconnected();
    }

    public function afterWatch(): void
    {
        foreach ($this->connections as $connection) {
            $connection->tick();
        }
        $this->dispatch('tick', [$this]);
    }

    // Detach connections no longer available
    protected function detachUnconnected(): void
    {
        foreach ($this->connections as $key => $connection) {
            if (!$connection->isConnected()) {
                $this->watcher->detach($key);
                unset($this->connections[$key]);
                $this->configuration->getLogger()->info("[{$this->identity}] Disconnected {$key}.");
                $this->dispatch('disconnect', [$this, $connection]);
            }
        }
    }

    // Perform upgrade handshake on new connections.
    protected function performHandshake(Connection $connection): ServerRequest
    {
        $response = $this->httpFactory->createResponse(101);
        $exception = null;

        // Read handshake request
        /** @var ServerRequest */
        $request = $connection->pullHttp();

        // Verify handshake request
        try {
            if ($request->getMethod() != 'GET') {
                throw new HandshakeException(
                    "Handshake request with invalid method: '{$request->getMethod()}'",
                    $response->withStatus(405)
                );
            }
            $connectionHeader = trim($request->getHeaderLine('Connection'));
            if (!str_contains(strtolower($connectionHeader), 'upgrade')) {
                throw new HandshakeException(
                    "Handshake request with invalid Connection header: '{$connectionHeader}'",
                    $response->withStatus(426)
                );
            }
            $upgradeHeader = trim($request->getHeaderLine('Upgrade'));
            if (strtolower($upgradeHeader) != 'websocket') {
                throw new HandshakeException(
                    "Handshake request with invalid Upgrade header: '{$upgradeHeader}'",
                    $response->withStatus(426)
                );
            }
            $versionHeader = trim($request->getHeaderLine('Sec-WebSocket-Version'));
            if ($versionHeader != '13') {
                throw new HandshakeException(
                    "Handshake request with invalid Sec-WebSocket-Version header: '{$versionHeader}'",
                    $response->withStatus(426)->withHeader('Sec-WebSocket-Version', '13')
                );
            }
            $keyHeader = trim($request->getHeaderLine('Sec-WebSocket-Key'));
            if (empty($keyHeader)) {
                throw new HandshakeException(
                    "Handshake request with invalid Sec-WebSocket-Key header: '{$keyHeader}'",
                    $response->withStatus(426)
                );
            }
            if (strlen(base64_decode($keyHeader)) != 16) {
                throw new HandshakeException(
                    "Handshake request with invalid Sec-WebSocket-Key header: '{$keyHeader}'",
                    $response->withStatus(426)
                );
            }

            $responseKey = base64_encode(pack('H*', sha1($keyHeader . Constant::GUID)));
            $response = $response
                ->withHeader('Upgrade', 'websocket')
                ->withHeader('Connection', 'Upgrade')
                ->withHeader('Sec-WebSocket-Accept', $responseKey);
        } catch (HandshakeException $e) {
            $this->configuration->getLogger()->warning("[{$this->identity}] {$e->getMessage()}", ['exception' => $e]);
            $response = $e->getResponse();
            $exception = $e;
        }

        // Respond to handshake
        /** @var Response */
        $response = $connection->pushHttp($response);
        if ($response->getStatusCode() != 101) {
            $exception = new HandshakeException("Invalid status code {$response->getStatusCode()}", $response);
        }

        if ($exception) {
            throw $exception;
        }

        $this->configuration->getLogger()->debug("[{$this->identity}] Handshake on {$request->getUri()->getPath()}");
        $connection->setHandshakeRequest($request);
        $connection->setHandshakeResponse($response);

        return $request;
    }
}
