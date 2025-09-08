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
    ListenerTrait,
    LoggerAwareTrait,
    SendMethodsTrait,
    StringableTrait
};

/**
 * WebSocket\Server class.
 * Entry class for WebSocket server.
 */
class Server implements LoggerAwareInterface, Stringable
{
    /** @use ListenerTrait<Server> */
    use ListenerTrait;
    use LoggerAwareTrait;
    use SendMethodsTrait;
    use StringableTrait;

    // Settings
    private int $port;
    private string $scheme;
    /** @var int<0, max>|float $timeout */
    private int|float $timeout = 60;
    /** @var int<1, max> $frameSize */
    private int $frameSize = 4096;
    private Context $context;

    // Internal resources
    private StreamFactory $streamFactory;
    private SocketServer|null $server = null;
    private StreamCollection|null $streams = null;
    private bool $running = false;
    /** @var array<Connection> $connections */
    private array $connections = [];
    /** @var array<MiddlewareInterface> $middlewares */
    private array $middlewares = [];
    private int|null $maxConnections = null;
    private HttpFactory $httpFactory;


    /* ---------- Magic methods ------------------------------------------------------------------------------------ */

    /**
     * @param int $port Socket port to listen to
     * @param bool $ssl If SSL should be used
     * @throws InvalidArgumentException If invalid port provided
     */
    public function __construct(int $port = 80, bool $ssl = false)
    {
        if ($port < 0 || $port > 65535) {
            throw new InvalidArgumentException("Invalid port '{$port}' provided");
        }
        $this->port = $port;
        $this->scheme = $ssl ? 'ssl' : 'tcp';
        $this->initLogger();
        $this->context = new Context();
        $this->httpFactory = new DefaultHttpFactory();
        $this->setStreamFactory(new StreamFactory());
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
        $this->logger = $logger;
        foreach ($this->connections as $connection) {
            $connection->setLogger($this->logger);
        }
    }

    /**
     * Set timeout.
     * @param int<0, max>|float $timeout Timeout in seconds
     * @return self
     * @throws InvalidArgumentException If invalid timeout provided
     */
    public function setTimeout(int|float $timeout): self
    {
        if ($timeout < 0) {
            throw new InvalidArgumentException("Invalid timeout '{$timeout}' provided");
        }
        $this->timeout = $timeout;
        foreach ($this->connections as $connection) {
            $connection->setTimeout($timeout);
        }
        return $this;
    }

    /**
     * Get timeout.
     * @return int<0, max>|float Timeout in seconds
     */
    public function getTimeout(): int|float
    {
        return $this->timeout;
    }

    /**
     * Set frame size.
     * @param int<1, max> $frameSize Frame size in bytes
     * @return self
     * @throws InvalidArgumentException If invalid frameSize provided
     */
    public function setFrameSize(int $frameSize): self
    {
        if ($frameSize < 3) {
            throw new InvalidArgumentException("Invalid frameSize '{$frameSize}' provided");
        }
        $this->frameSize = $frameSize;
        foreach ($this->connections as $connection) {
            $connection->setFrameSize($frameSize);
        }
        return $this;
    }

    /**
     * Get frame size.
     * @return int Frame size in bytes
     */
    public function getFrameSize(): int
    {
        return $this->frameSize;
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
     * @param Context|array<string, mixed> $context Context or options as array
     * @see https://www.php.net/manual/en/context.php
     * @return self
     */
    public function setContext(Context|array $context): self
    {
        if ($context instanceof Context) {
            $this->context = $context;
        } else {
            $this->context->setOptions($context);
            trigger_error('Calling Server.setContext with array is deprecated, use Context class.', E_USER_DEPRECATED);
        }
        return $this;
    }

    /**
     * Get current stream context.
     * @return Context
     */
    public function getContext(): Context
    {
        return $this->context;
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
     * @param int|null $maxConnections
     * @return self
     * @throws InvalidArgumentException If number provided
     */
    public function setMaxConnections(int|null $maxConnections): self
    {
        if ($maxConnections !== null && $maxConnections < 1) {
            throw new InvalidArgumentException("Invalid maxConnections '{$maxConnections}' provided");
        }
        $this->maxConnections = $maxConnections;
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
     * @throws Throwable On low level error
     */
    public function start(int|float|null $timeout = null): void
    {
        // Create socket server
        if (empty($this->server)) {
            $this->createSocketServer();
        }

        // Check if running
        if ($this->running) {
            $this->logger->warning("[server] Server is already running");
            return;
        }
        $this->running = true;
        $this->logger->info("[server] Server is running");

        /** @var StreamCollection */
        $streams = $this->streams;

        // Run handler
        while ($this->running) {
            try {
                // Clear closed connections
                $this->detachUnconnected();
                if (is_null($this->streams)) {
                    $this->stop();
                    return;
                }

                // Get streams with readable content
                $readables = $this->streams->waitRead($timeout ?? $this->timeout);
                foreach ($readables as $key => $readable) {
                    try {
                        $connection = null;
                        // Accept new client connection
                        if ($readable instanceof SocketServer) {
                            $this->acceptSocket($readable);
                            continue;
                        }
                        // Read from connection
                        $connection = $this->connections[$key];
                        $message = $connection->pullMessage();
                        $this->dispatch($message->getOpcode(), [$this, $connection, $message]);
                    } catch (MessageLevelInterface $e) {
                        // Error, but keep connection open
                        $this->logger->error("[server] {$e->getMessage()}", ['exception' => $e]);
                        $this->dispatch('error', [$this, $connection, $e]);
                    } catch (ConnectionLevelInterface $e) {
                        // Error, disconnect connection
                        if ($connection) {
                            $this->streams()->detach($key);
                            unset($this->connections[$key]);
                            $connection->disconnect();
                        }
                        $this->logger->error("[server] {$e->getMessage()}", ['exception' => $e]);
                        $this->dispatch('error', [$this, $connection, $e]);
                    } catch (CloseException $e) {
                        // Should close
                        if ($connection) {
                            $connection->close($e->getCloseStatus(), $e->getMessage());
                        }
                        $this->logger->error("[server] {$e->getMessage()}", ['exception' => $e]);
                        $this->dispatch('error', [$this, $connection, $e]);
                    }
                }
                foreach ($this->connections as $connection) {
                    $connection->tick();
                }
                $this->dispatch('tick', [$this]);
            } catch (ExceptionInterface $e) {
                // Low-level error
                $this->logger->error("[server] {$e->getMessage()}", ['exception' => $e]);
                $this->dispatch('error', [$this, null, $e]);
            } catch (Throwable $e) {
                // Crash it
                $this->logger->error("[server] {$e->getMessage()}", ['exception' => $e]);
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
        $this->logger->info("[server] Server is stopped");
    }

    /**
     * If server is running (accepting connections and messages).
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->running;
    }


    /* ---------- Connection management ---------------------------------------------------------------------------- */

    /**
     * Orderly shutdown of server.
     * @param int $closeStatus Default is 1001 "Going away"
     */
    public function shutdown(int $closeStatus = 1001): void
    {
        $this->logger->info('[server] Shutting down');
        if ($this->getConnectionCount() == 0) {
            $this->disconnect();
            return;
        }
        // Store and reset settings, lock new connections, reset listeners
        $max = $this->maxConnections;
        $this->maxConnections = 0;
        $listeners = $this->listeners;
        $this->listeners = [];
        // Track disconnects
        $this->onDisconnect(function () use ($max, $listeners) {
            if ($this->getConnectionCount() > 0) {
                return;
            }
            $this->disconnect();
            // Restore settings
            $this->maxConnections = $max;
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
        foreach ($this->connections as $connection) {
            $connection->disconnect();
            $this->dispatch('disconnect', [$this, $connection]);
        }
        $this->connections = [];
        if ($this->server) {
            $this->server->close();
        }
        $this->server = $this->streams = null;
        $this->logger->info('[server] Server disconnected');
    }


    /* ---------- Internal helper methods -------------------------------------------------------------------------- */

    // Create socket server
    protected function createSocketServer(): void
    {
        try {
            $uri = new Uri("{$this->scheme}://0.0.0.0:{$this->port}");
            $this->server = $this->streamFactory->createSocketServer($uri, $this->context);
            $this->streams = $this->streamFactory->createStreamCollection();
            $this->streams->attach($this->server, '@server');
            $this->logger->info("[server] Starting server on {$uri}.");
        } catch (Throwable $e) {
            $error = "Server failed to start: {$e->getMessage()}";
            throw new ServerException($error);
        }
    }

    // Accept connection on socket server
    protected function acceptSocket(SocketServer $socket): void
    {
        if (!is_null($this->maxConnections) && $this->getConnectionCount() >= $this->maxConnections) {
            $this->logger->warning("[server] Denied connection, reached max {$this->maxConnections}");
            return;
        }
        try {
            /** @var SocketStream $stream */
            $stream = $socket->accept();
            $name = $stream->getRemoteName();
            $this->streams()->attach($stream, $name);
            $connection = new Connection(
                $stream,
                false,
                true,
                $this->isSsl(),
                $this->httpFactory
            );
        } catch (StreamException $e) {
            throw new ConnectionFailureException("Server failed to accept: {$e->getMessage()}");
        }
        try {
            $connection->setLogger($this->logger);
            $connection
                ->setFrameSize($this->frameSize)
                ->setTimeout($this->timeout)
                ;
            foreach ($this->middlewares as $middleware) {
                $connection->addMiddleware($middleware);
            }
            /** @throws StreamException */
            $request = $this->performHandshake($connection);
            $this->connections[$name] = $connection;
            $this->logger->info("[server] Accepted connection from {$name}.");
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

    // Detach connections no longer available
    protected function detachUnconnected(): void
    {
        foreach ($this->connections as $key => $connection) {
            if (!$connection->isConnected()) {
                $this->streams()->detach($key);
                unset($this->connections[$key]);
                $this->logger->info("[server] Disconnected {$key}.");
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
            $this->logger->warning("[server] {$e->getMessage()}", ['exception' => $e]);
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

        $this->logger->debug("[server] Handshake on {$request->getUri()->getPath()}");
        $connection->setHandshakeRequest($request);
        $connection->setHandshakeResponse($response);

        return $request;
    }

    protected function streams(): StreamCollection
    {
        /** @var StreamCollection $streams */
        $streams = $this->streams;
        return $streams;
    }
}
