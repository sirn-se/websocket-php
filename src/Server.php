<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
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
    StreamContainerInterface,
    StreamException,
    StreamFactory,
    StreamInterface,
    Uri
};
<<<<<<< HEAD
=======
use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface,
};
>>>>>>> v4.0-main
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
use WebSocket\Http\DefaultHttpFactory;
use WebSocket\Message\Message;
use WebSocket\Middleware\MiddlewareInterface;
use WebSocket\Runtime\{
    Connections,
    IdentityInterface,
    Runner,
};
use WebSocket\Trait\{
    ConfigurationTrait,
    ListenerTrait,
    SendMethodsTrait,
    StringableTrait
};
use WebSocket\Runtime\{
    HandlerInterface,
    SelectableInterface,
    IdentityInterface,
    Watcher,
};

/**
 * WebSocket\Server class.
 * Entry class for WebSocket server.
 */
<<<<<<< HEAD
class Server implements HandlerInterface, IdentityInterface, LoggerAwareInterface, SelectableInterface, Stringable
{
    use ConfigurationTrait;
    /** @use ListenerTrait<Server> */
=======
class Server implements IdentityInterface, LoggerAwareInterface, StreamContainerInterface, Stringable
{
    use ConfigurationTrait;
    /** @use ListenerTrait<Server, Message> */
>>>>>>> v4.0-main
    use ListenerTrait;
    use SendMethodsTrait;
    use StringableTrait;

    private const SCOPE = 'server';

    // Settings
    private int $port;
    private string $scheme;

    // Internal resources
    private SocketServer|null $server = null;
<<<<<<< HEAD
=======
    private Runner $runner;

>>>>>>> v4.0-main
    private bool $running = false;
    private Connections $connections;
    /** @var array<MiddlewareInterface> $middlewares */
    private array $middlewares = [];
    private bool $allowConnections = false;
<<<<<<< HEAD

    private StreamFactory $streamFactory;
    private HttpFactory $httpFactory;
    private Watcher $watcher;
=======
    private HttpFactory $httpFactory;
>>>>>>> v4.0-main
    /** @var non-empty-string $identity */
    private string $identity;


    /* ---------- Magic methods ------------------------------------------------------------------------------------ */

    /**
     * @param int<0, 65535> $port Socket port to listen to
     * @param bool $ssl If SSL should be used
<<<<<<< HEAD
     * @param StreamFactory|null $streamFactory
     * @param HttpFactory|null $httpFactory
     * @param Watcher|null $watcher
     * @param Configuration|null $configuration
=======
     * @param Configuration|null $configuration
     * @param StreamFactory|null $streamFactory
     * @param HttpFactory|null $httpFactory
     * @param Runner|null $runner
>>>>>>> v4.0-main
     * @throws InvalidArgumentException If invalid port provided
     */
    public function __construct(
        int $port = 80,
        bool $ssl = false,
<<<<<<< HEAD
        StreamFactory|null $streamFactory = null,
        HttpFactory|null $httpFactory = null,
        Watcher|null $watcher = null,
        Configuration|null $configuration = null,
=======
        Configuration|null $configuration = null,
        StreamFactory|null $streamFactory = null,
        HttpFactory|null $httpFactory = null,
        Runner|null $runner = null,
>>>>>>> v4.0-main
    ) {
        if ($port < 0 || $port > 65535) {
            throw new InvalidArgumentException("Invalid port '{$port}' provided");
        }
        $this->port = $port;
        $this->scheme = $ssl ? 'ssl' : 'tcp';
        $this->streamFactory = $streamFactory ?? new StreamFactory();
        $this->httpFactory = $httpFactory ?? new DefaultHttpFactory();
<<<<<<< HEAD
        $this->watcher = $watcher ?? new Watcher($this->streamFactory->createStreamCollection());
        $this->initConfiguration($configuration);
        $this->identity = "server/{$port}";
=======
        $this->identity = "server/{$port}";
        $this->initConfiguration($configuration);
        $this->runner = $runner ?? new Runner($this->streamFactory);
        $this->connections = new Connections(false, true, $this->httpFactory, $this->configuration);
>>>>>>> v4.0-main
    }

    /**
     * Get string representation of instance.
     * @return string String representation
     */
    public function __toString(): string
    {
        return $this->stringable('%s', $this->server ? "{$this->scheme}://0.0.0.0:{$this->port}" : 'closed');
    }

    public function getIdentity(): string
    {
        return $this->identity;
    }


    /* ---------- Configuration ------------------------------------------------------------------------------------ */

    public function getIdentity(): string
    {
        return $this->identity;
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
     * @deprecated Will be removed in future version, set on Configuration instead
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
     * @deprecated Will be removed in future version, set on Configuration instead
     */
    public function setTimeout(int|float $timeout): self
    {
        $this->configuration->setTimeout($timeout);
        return $this;
    }

    /**
     * Get timeout.
     * @return int<0, max>|float Timeout in seconds
     * @deprecated Will be removed in future version, get from Configuration instead
     */
    public function getTimeout(): int|float
    {
        return $this->configuration->getTimeout();
    }

    /**
     * Set frame size.
     * @param int<1, max> $frameSize Max frame payload size in bytes
     * @return self
<<<<<<< HEAD
=======
     * @throws InvalidArgumentException If invalid frameSize provided
     * @deprecated Will be removed in future version, set on Configuration instead
>>>>>>> v4.0-main
     */
    public function setFrameSize(int $frameSize): self
    {
        $this->configuration->setFrameSize($frameSize);
        return $this;
    }

    /**
     * Get frame size.
     * @return int Frame size in bytes
     * @deprecated Will be removed in future version, get from Configuration instead
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
     * @return int<0, max> Connection count
     */
    public function getConnectionCount(): int
    {
        return count($this->connections);
    }

    /**
     * Get currently connected clients.
     * @return array<non-empty-string, Connection> Connections
     */
    public function getConnections(): array
    {
        return $this->connections->toArray();
    }

    /**
     * Get currently readable clients.
     * @return array<Connection> Connections
     */
    public function getReadableConnections(): array
    {
        return $this->connections->filter(function (Connection $connection) {
            return $connection->isReadable();
        })->toArray();
    }

    /**
     * Get currently writable clients.
     * @return array<Connection> Connections
     */
    public function getWritableConnections(): array
    {
        return $this->connections->filter(function (Connection $connection) {
            return $connection->isWritable();
        })->toArray();
    }

    /**
     * Set stream context.
<<<<<<< HEAD
     * @param Context $context Context or options as array
=======
     * @param Context $context Context
>>>>>>> v4.0-main
     * @see https://www.php.net/manual/en/context.php
     * @return self
     * @deprecated Will be removed in future version, set on Configuration instead
     */
    public function setContext(Context $context): self
    {
        $this->configuration->setContext($context);
        return $this;
    }

    /**
     * Get current stream context.
     * @return Context
     * @deprecated Will be removed in future version, get from Configuration instead
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
        $this->connections->walk(function (Connection $connection) use ($middleware) {
            $connection->addMiddleware($middleware);
        });
        return $this;
    }

    /**
     * Set maximum number of connections allowed, null means unlimited.
     * @param int<1, max>|null $maxConnections
     * @return self
<<<<<<< HEAD
=======
     * @throws InvalidArgumentException If number provided
     * @deprecated Will be removed in future version, set on Configuration instead
>>>>>>> v4.0-main
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
        foreach ($this->getConnections() as $connection) {
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
<<<<<<< HEAD
            $this->configuration->getLogger()->warning('[{identity}] Server is already running', [
                'identity' => $this->identity,
=======
            $this->configuration->getLogger()->warning("[{scope}] Server is already running", [
                'scope' => self::SCOPE,
                'server' => $this->identity,
>>>>>>> v4.0-main
            ]);
            return;
        }
        $this->beforeStart();
        $this->running = true;
<<<<<<< HEAD
        $this->configuration->getLogger()->info('[{identity}] Server is running', [
            'identity' => $this->identity,
=======
        $this->configuration->getLogger()->info("[{scope}] Server is running", [
            'scope' => self::SCOPE,
            'server' => $this->identity,
>>>>>>> v4.0-main
        ]);

        // Run handler
        while ($this->running) {
            try {
<<<<<<< HEAD
                $this->beforeWatch();
                if ($this->watcher->isEmpty()) {
                    $this->stop();
                    return;
                }
                $this->watcher->watch($timeout ?? $this->configuration->getTimeout());
                $this->afterWatch();
            } catch (ExceptionInterface $e) {
                // Low-level error
                $this->configuration->getLogger()->error('[{identity}] {error}', [
                    'identity' => $this->identity,
                    'exception' => $e,
                    'error' => $e->getMessage(),
=======
                // Clear closed connections
                $this->detachUnconnected();

                if (!$this->server) {
                    $this->stop();
                    return;
                }

                // Run attached handlers on selected streams
                $this->runner->handle($timeout ?? $this->configuration->getTimeout());

                foreach ($this->getConnections() as $connection) {
                    $connection->tick();
                }
                $this->dispatch('tick', [$this]);
            } catch (ExceptionInterface $e) {
                // Low-level error
                $this->configuration->getLogger()->error("[{scope}] {message}", [
                    'scope' => self::SCOPE,
                    'server' => $this->identity,
                    'exception' => $e,
                    'message' => $e->getMessage(),
>>>>>>> v4.0-main
                ]);
                $this->dispatch('error', [$this, null, $e]);
            } catch (Throwable $e) {
                // Crash it
<<<<<<< HEAD
                $this->configuration->getLogger()->error('[{identity}] {error}', [
                    'identity' => $this->identity,
                    'exception' => $e,
                    'error' => $e->getMessage(),
=======
                $this->configuration->getLogger()->error("[{scope}] {message}", [
                    'scope' => self::SCOPE,
                    'server' => $this->identity,
                    'exception' => $e,
                    'message' => $e->getMessage(),
>>>>>>> v4.0-main
                ]);
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
<<<<<<< HEAD
        $this->configuration->getLogger()->info('[{identity}] Server is stopped', [
            'identity' => $this->identity,
=======
        $this->configuration->getLogger()->info("[{scope}] Server is stopped", [
            'scope' => self::SCOPE,
            'server' => $this->identity,
>>>>>>> v4.0-main
        ]);
    }

    /**
     * If server is running (accepting connections and messages).
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->running;
    }

    public function selectHandler(Connection $connection): void
    {
        $key = $connection->getIdentity();
        try {
            // Read from connection
            $message = $connection->pullMessage();
            $this->dispatch($message->getOpcode(), [$this, $connection, $message]);
        } catch (MessageLevelInterface $e) {
            // Error, but keep connection open
            $this->configuration->getLogger()->error('[{identity}] {error} (message)', [
                'identity' => $this->identity,
                'exception' => $e,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('error', [$this, $connection, $e]);
        } catch (ConnectionLevelInterface $e) {
            // Error, disconnect connection
            if ($connection) {
                $this->watcher->detach($connection->getIdentity());
                unset($this->connections[$key]);
                $connection->disconnect();
            }
            $this->configuration->getLogger()->error('[{identity}] {error} (connection)', [
                'identity' => $this->identity,
                'exception' => $e,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('error', [$this, $connection, $e]);
        } catch (CloseException $e) {
            // Should close
            if ($connection) {
                $connection->close($e->getCloseStatus(), $e->getMessage());
            }
            $this->configuration->getLogger()->info('[{identity}] {error} (close)', [
                'identity' => $this->identity,
                'exception' => $e,
                'error' => $e->getMessage(),
                'status' => $e->getCloseStatus(),
            ]);
            $this->dispatch('error', [$this, $connection, $e]);
        }
    }


    /* ---------- Connection management ---------------------------------------------------------------------------- */

    /**
     * Orderly shutdown of server.
     * @param int<0, 4999> $closeStatus Default is 1001 "Going away"
     */
    public function shutdown(int $closeStatus = 1001): void
    {
<<<<<<< HEAD
        $this->configuration->getLogger()->info('[{identity}] Shutting down', [
            'identity' => $this->identity,
=======
        $this->configuration->getLogger()->info("[{scope}] Shutting down", [
            'scope' => self::SCOPE,
            'server' => $this->identity,
>>>>>>> v4.0-main
        ]);
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
<<<<<<< HEAD
        $this->watcher->detach($this->getIdentity());
        foreach ($this->connections as $connection) {
            $connection->disconnect();
            $this->dispatch('disconnect', [$this, $connection]);
=======
        foreach ($this->getConnections() as $connection) {
            $this->disconnectConnection($connection);
>>>>>>> v4.0-main
        }
        $this->connections->reset();
        if ($this->server) {
            $this->server->close();
            $this->runner->detach($this->identity);
        }
        $this->server = null;
<<<<<<< HEAD
        $this->configuration->getLogger()->info('[{identity}] Server disconnected', [
            'identity' => $this->identity,
=======
        $this->configuration->getLogger()->info("[{scope}] Server disconnected", [
            'scope' => self::SCOPE,
            'server' => $this->identity,
>>>>>>> v4.0-main
        ]);
    }

    public function getStream(): SocketServer
    {
        return $this->server ?? $this->createSocketServer();
<<<<<<< HEAD
    }

    public function onSelect(): void
    {
        if ($this->server) {
            $this->acceptHandler($this->server);
        }
=======
>>>>>>> v4.0-main
    }


    /* ---------- Internal helper methods -------------------------------------------------------------------------- */

<<<<<<< HEAD
    /**
     * Create socket server
     * @throws ServerException Socket server could not be created
     */
=======
    protected function disconnectConnection(Connection $connection): void
    {
        $connection->disconnect();
        $this->runner->detach($connection->getIdentity());
        $this->dispatch('disconnect', [$this, $connection]);
    }

    // Create socket server
>>>>>>> v4.0-main
    protected function createSocketServer(): SocketServer
    {
        try {
            $uri = new Uri("{$this->scheme}://0.0.0.0:{$this->port}");
<<<<<<< HEAD
            $this->server = $this->streamFactory->createSocketServer($uri, $this->configuration->getContext());
            /** @throws StreamException */
            $this->watcher->attach($this->getIdentity(), $this->server, function (string $key, SocketServer $socket) {
                $this->acceptHandler($socket);
            });
            $this->allowConnections = true;
            $this->configuration->getLogger()->info('[{identity}] Starting server on {uri}', [
                'identity' => $this->identity,
                'uri' => $uri,
            ]);
        } catch (StreamException $e) {
=======
            $this->server = $server = $this->streamFactory->createSocketServer(
                $uri,
                $this->configuration->getContext()
            );
            $this->runner->attach($this, function (Runner $runner, Server $server) {
                $this->acceptSocket($server->getStream());
            }, $this->getIdentity());
            $this->allowConnections = true;
            $this->configuration->getLogger()->info("[{scope}] Starting server on {uri}", [
                'scope' => self::SCOPE,
                'server' => $this->identity,
                'uri' => $uri,
            ]);
            return $server;
        } catch (Throwable $e) {
>>>>>>> v4.0-main
            $error = "Server failed to start: {$e->getMessage()}";
            $this->configuration->getLogger()->error('[{identity}] {error}', [
                'identity' => $this->identity,
                'exception' => $e,
                'error' => $error,
            ]);
            throw new ServerException($this, $error, $e);
        } catch (Throwable $e) {
            $error = "Server error: {$e->getMessage()}";
            $this->configuration->getLogger()->error('[{identity}] {error}', [
                'identity' => $this->identity,
                'exception' => $e,
                'error' => $error,
            ]);
            throw $e;
        }
        if (is_null($this->server)) {
            $error = "Server failed to start.";
            $this->configuration->getLogger()->error("[{$this->identity}] {$error}");
            throw new ServerException($this, $error);
        }
        return $this->server;
    }

<<<<<<< HEAD
    // Accept connection on socket server
    protected function acceptHandler(SocketServer $socket): void
    {
        $maxConnections = $this->configuration->getMaxConnections();
        if (!is_null($maxConnections) && $this->getConnectionCount() >= $maxConnections) {
            $this->configuration->getLogger()->warning('[{identity}] Denied connection, reached max {maxConnections}', [
                'identity' => $this->identity,
=======
    /**
     * Accept connection on socket server
     * @throws ConnectionFailureException
     */
    protected function acceptSocket(SocketServer $socket): void
    {
        $maxConnections = $this->configuration->getMaxConnections();
        if (!is_null($maxConnections) && $this->getConnectionCount() >= $maxConnections) {
            $this->configuration->getLogger()->warning("[{scope}] Denied connection, reached max {maxConnections}", [
                'scope' => self::SCOPE,
                'server' => $this->identity,
                'connections' => $this->getConnectionCount(),
>>>>>>> v4.0-main
                'maxConnections' => $maxConnections,
            ]);
            return;
        }
        if (!$this->allowConnections) {
<<<<<<< HEAD
            $this->configuration->getLogger()->warning("[server] Denied connection, shutting down");
            $this->configuration->getLogger()->warning('[{identity}] ', [
                'identity' => $this->identity,
=======
            $this->configuration->getLogger()->warning("[{scope}] Denied connection, shutting down", [
                'scope' => self::SCOPE,
                'server' => $this->identity,
>>>>>>> v4.0-main
            ]);
            return;
        }
        try {
            /** @var SocketStream $stream */
            $stream = $socket->accept();
<<<<<<< HEAD
            $connection = new Connection(
                $this,
                $stream,
                false,
                true,
                $this->isSsl(),
                $this->httpFactory,
                $this->configuration,
            );
            $this->watcher->attach($connection);
            $this->watcher->attach($connection->getIdentity(), $stream, function ($key, $stream) {
                $this->selectHandler($key, $stream);
            });
=======
            $connection = $this->connections->create($stream, $this->isSsl());

            $this->runner->attach($connection, function (Runner $runner, Connection $connection) {
                $key = $connection->getIdentity();

                try {
                    $message = $connection->pullMessage();
                    $this->dispatch($message->getOpcode(), [$this, $connection, $message]);
                    $this->dispatch('message', [$this, $connection, $message]);
                } catch (MessageLevelInterface $e) {
                    // Error, but keep connection open
                    $this->configuration->getLogger()->error("[{scope}] {message}", [
                        'scope' => self::SCOPE,
                        'server' => $this->identity,
                        'connection' => $connection->getIdentity(),
                        'exception' => $e,
                        'message' => $e->getMessage(),
                    ]);
                    $this->dispatch('error', [$this, $connection, $e]);
                } catch (ConnectionLevelInterface $e) {
                    // Error, disconnect connection
                    $this->connections->detach($key);
                    $this->disconnectConnection($connection);
                    $this->configuration->getLogger()->error("[{scope}] {message}", [
                        'scope' => self::SCOPE,
                        'server' => $this->identity,
                        'exception' => $e,
                        'message' => $e->getMessage(),
                    ]);
                    $this->dispatch('error', [$this, $connection, $e]);
                } catch (CloseException $e) {
                    // Should close
                    $connection->close($e->getCloseStatus(), $e->getMessage());
                    $this->configuration->getLogger()->error("[{scope}] {message}", [
                        'scope' => self::SCOPE,
                        'server' => $this->identity,
                        'connection' => $connection->getIdentity(),
                        'exception' => $e,
                        'message' => $e->getMessage(),
                    ]);
                    $this->dispatch('error', [$this, $connection, $e]);
                }
            }, $connection->getIdentity());
>>>>>>> v4.0-main
        } catch (StreamException $e) {
            throw new ConnectionFailureException(null, "Server failed to accept: {$e->getMessage()}", $e);
        }
        try {
            foreach ($this->middlewares as $middleware) {
                $connection->addMiddleware($middleware);
            }
            /** @throws StreamException */
            $request = $this->performHandshake($connection);
<<<<<<< HEAD
            $this->connections[$connection->getIdentity()] = $connection;
            $this->configuration->getLogger()->info('[{identity}] Accepted connection from {connection}', [
                'identity' => $this->identity,
                'connection' => $connection->getIdentity(),
            ]);
=======
            $this->connections->attach($connection);
            $this->configuration->getLogger()->info("[{scope}] Accepted connection from {connection}", [
                'scope' => self::SCOPE,
                'server' => $this->identity,
                'connection' => $connection->getIdentity(),
            ]);

>>>>>>> v4.0-main
            $this->dispatch('handshake', [
                $this,
                $connection,
                $connection->getHandshakeRequest(),
                $connection->getHandshakeResponse(),
            ]);
            $this->dispatch('connect', [$this, $connection, $request]);
        } catch (ExceptionInterface | StreamException $e) {
            $this->runner->detach($connection->getIdentity());
            $connection->disconnect();
            throw new ConnectionFailureException($connection, "Server failed to accept: {$e->getMessage()}", $e);
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
        foreach ($this->getConnections() as $key => $connection) {
            if (!$connection->isConnected()) {
<<<<<<< HEAD
                $this->watcher->detach($key);
                unset($this->connections[$key]);
                $this->configuration->getLogger()->info('[{identity}] Disconnected {key}.', [
                    'identity' => $this->identity,
=======
                $this->runner->detach($key);
                $this->connections->detach($key);
                $this->configuration->getLogger()->info("[{scope}] Disconnected {connection}", [
                    'scope' => self::SCOPE,
                    'server' => $this->identity,
>>>>>>> v4.0-main
                    'connection' => $connection->getIdentity(),
                ]);
                $this->dispatch('disconnect', [$this, $connection]);
            }
        }
    }

    // Perform upgrade handshake on new connections.
    protected function performHandshake(Connection $connection): ServerRequestInterface
    {
<<<<<<< HEAD
        $response = $this->httpFactory->createResponse(101);
=======
        $response = $this->httpFactory->createResponse(101, 'Switching Protocols');
>>>>>>> v4.0-main
        $exception = null;

        // Read handshake request
        /** @var ServerRequestInterface */
        $request = $connection->pullHttp();

        // Verify handshake request
        try {
            if ($request->getMethod() != 'GET') {
                throw new HandshakeException(
<<<<<<< HEAD
                    $connection,
                    $response->withStatus(405),
                    "Handshake request with invalid method: '{$request->getMethod()}'",
=======
                    'Handshake request with invalid method: {method}',
                    response: $response->withStatus(405),
                    method: $request->getMethod(),
>>>>>>> v4.0-main
                );
            }
            $connectionHeader = trim($request->getHeaderLine('Connection'));
            if (!str_contains(strtolower($connectionHeader), 'upgrade')) {
                throw new HandshakeException(
<<<<<<< HEAD
                    $connection,
                    $response->withStatus(426),
                    "Handshake request with invalid Connection header: '{$connectionHeader}'",
=======
                    'Handshake request with invalid {headerName} header: {headerValue}',
                    response: $response->withStatus(426),
                    headerName: 'Connection',
                    headerValue: $connectionHeader,
>>>>>>> v4.0-main
                );
            }
            $upgradeHeader = trim($request->getHeaderLine('Upgrade'));
            if (strtolower($upgradeHeader) != 'websocket') {
                throw new HandshakeException(
<<<<<<< HEAD
                    $connection,
                    $response->withStatus(426),
                    "Handshake request with invalid Upgrade header: '{$upgradeHeader}'",
=======
                    'Handshake request with invalid {headerName} header: {headerValue}',
                    response: $response->withStatus(426),
                    headerName: 'Upgrade',
                    headerValue: $upgradeHeader,
>>>>>>> v4.0-main
                );
            }
            $versionHeader = trim($request->getHeaderLine('Sec-WebSocket-Version'));
            if ($versionHeader != '13') {
                throw new HandshakeException(
<<<<<<< HEAD
                    $connection,
                    $response->withStatus(426)->withHeader('Sec-WebSocket-Version', '13'),
                    "Handshake request with invalid Sec-WebSocket-Version header: '{$versionHeader}'",
=======
                    'Handshake request with invalid {headerName} header: {headerValue}',
                    response: $response->withStatus(426)->withHeader('Sec-WebSocket-Version', '13'),
                    headerName: 'Sec-WebSocket-Version',
                    headerValue: $versionHeader,
>>>>>>> v4.0-main
                );
            }
            $keyHeader = trim($request->getHeaderLine('Sec-WebSocket-Key'));
            if (empty($keyHeader)) {
                throw new HandshakeException(
<<<<<<< HEAD
                    $connection,
                    $response->withStatus(426),
                    "Handshake request with invalid Sec-WebSocket-Key header: '{$keyHeader}'",
=======
                    'Handshake request with invalid {headerName} header: {headerValue}',
                    response: $response->withStatus(426),
                    headerName: 'Sec-WebSocket-Key',
                    headerValue: $keyHeader,
>>>>>>> v4.0-main
                );
            }
            if (strlen(base64_decode($keyHeader)) != 16) {
                throw new HandshakeException(
<<<<<<< HEAD
                    $connection,
                    $response->withStatus(426),
                    "Handshake request with invalid Sec-WebSocket-Key header: '{$keyHeader}'",
=======
                    'Handshake request with invalid {headerName} header: {headerValue}',
                    response: $response->withStatus(426),
                    headerName: 'Sec-WebSocket-Key',
                    headerValue: $keyHeader,
>>>>>>> v4.0-main
                );
            }

            $responseKey = base64_encode(pack('H*', sha1($keyHeader . Constant::GUID)));
            $response = $response
                ->withHeader('Upgrade', 'websocket')
                ->withHeader('Connection', 'Upgrade')
                ->withHeader('Sec-WebSocket-Accept', $responseKey);
        } catch (HandshakeException $e) {
<<<<<<< HEAD
            $this->configuration->getLogger()->warning('[{identity}] {error} (handshake)', [
                'identity' => $this->identity,
                'exception' => $e,
                'error' => $e->getMessage(),
=======
            $this->configuration->getLogger()->warning("[{scope}] {message}", [
                'scope' => self::SCOPE,
                'server' => $this->identity,
                'connection' => $connection->getIdentity(),
                'exception' => $e,
                'message' => $e->getMessage(),
>>>>>>> v4.0-main
            ]);
            $response = $e->getResponse();
            $exception = $e;
        }

        // Respond to handshake
        /** @var ResponseInterface */
        $response = $connection->pushHttp($response);
        if ($response->getStatusCode() != 101) {
            $exception = new HandshakeException(
<<<<<<< HEAD
                $connection,
                $response,
                "Invalid status code {$response->getStatusCode()}",
=======
                'Invalid status code {statusCode}',
                response: $response,
                statusCode: $response->getStatusCode(),
>>>>>>> v4.0-main
            );
        }

        if ($exception) {
            throw $exception;
        }

<<<<<<< HEAD
        $this->configuration->getLogger()->debug('[{identity}] Handshake on {path}', [
            'identity' => $this->identity,
=======
        $this->configuration->getLogger()->debug("[{scope}] Handshake on {path}", [
            'scope' => self::SCOPE,
            'server' => $this->identity,
            'connection' => $connection->getIdentity(),
>>>>>>> v4.0-main
            'path' => $request->getUri()->getPath(),
        ]);

        $connection->setHandshakeRequest($request);
        $connection->setHandshakeResponse($response);

        return $request;
    }
}
