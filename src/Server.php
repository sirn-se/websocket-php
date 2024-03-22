<?php

/**
 * Copyright (C) 2014-2024 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket;

use InvalidArgumentException;
use Phrity\Net\{
    SocketServer,
    StreamFactory,
    Uri
};
use Psr\Log\{
    LoggerAwareInterface,
    LoggerInterface,
    NullLogger
};
use Stringable;
use Throwable;
use WebSocket\Exception\{
    CloseException,
    ConnectionLevelInterface,
    Exception,
    HandshakeException,
    MessageLevelInterface,
    ServerException
};
use WebSocket\Http\{
    Response,
    ServerRequest
};
use WebSocket\Message\Message;
use WebSocket\Middleware\MiddlewareInterface;
use WebSocket\Trait\{
    ListenerTrait,
    SendMethodsTrait,
    StringableTrait
};

/**
 * WebSocket\Server class.
 * Entry class for WebSocket server.
 */
class Server implements LoggerAwareInterface, Stringable
{
    use ListenerTrait;
    use SendMethodsTrait;
    use StringableTrait;

    private const GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    // Settings
    private $port;
    private $scheme;
    private $logger;
    private $timeout = 60;
    private $frameSize = 4096;

    // Internal resources
    private $streamFactory;
    private $server;
    private $streams;
    private $running = false;
    private $connections = [];
    private $middlewares = [];


    /* ---------- Magic methods ------------------------------------------------------------------------------------ */

    /**
     * @param int $port Socket port to listen to
     * @param string $scheme Scheme (tcp or ssl)
     * @throws InvalidArgumentException If invalid port provided
     */
    public function __construct(int $port = 80, bool $ssl = false)
    {
        if ($port < 0 || $port > 65535) {
            throw new InvalidArgumentException("Invalid port '{$port}' provided");
        }
        $this->port = $port;
        $this->scheme = $ssl ? 'ssl' : 'tcp';
        $this->logger = new NullLogger();
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
     * @param Phrity\Net\StreamFactory $streamFactory
     * @return self
     */
    public function setStreamFactory(StreamFactory $streamFactory): self
    {
        $this->streamFactory = $streamFactory;
        return $this;
    }

    /**
     * Set logger.
     * @param Psr\Log\LoggerInterface $logger Logger implementation
     * @return self
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        foreach ($this->connections as $connection) {
            $connection->setLogger($this->logger);
        }
        return $this;
    }

    /**
     * Set timeout.
     * @param int $timeout Timeout in seconds
     * @return self
     * @throws InvalidArgumentException If invalid timeout provided
     */
    public function setTimeout(int $timeout): self
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
     * @return int Timeout in seconds
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Set frame size.
     * @param int $frameSize Frame size in bytes
     * @return self
     * @throws InvalidArgumentException If invalid frameSize provided
     */
    public function setFrameSize(int $frameSize): self
    {
        if ($frameSize < 1) {
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
     * @return string scheme
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
     * Add a middleware.
     * @param WebSocket\Middleware\MiddlewareInterface $middleware
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


    /* ---------- Messaging operations ----------------------------------------------------------------------------- */

    /**
     * Send message (broadcast to all connected clients).
     * @param WebSocket\Message\Message $message Message to send
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
    public function start(): void
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

        // Run handler
        while ($this->running) {
            try {
                // Clear closed connections
                $this->detachUnconnected();

                // Get streams with readable content
                $readables = $this->streams->waitRead($this->timeout);
                foreach ($readables as $key => $readable) {
                    try {
                        $connection = null;
                        // Accept new client connection
                        if ($key == '@server') {
                            $this->acceptSocket($readable);
                            continue;
                        }
                        // Read from connection
                        $connection = $this->connections[$key];
                        if ($message = $connection->pullMessage()) {
                            $this->dispatch($message->getOpcode(), [$this, $connection, $message]);
                        }
                    } catch (MessageLevelInterface $e) {
                        // Error, but keep connection open
                        $this->logger->error("[server] {$e->getMessage()}");
                        $this->dispatch('error', [$this, $connection, $e]);
                    } catch (ConnectionLevelInterface $e) {
                        // Error, disconnect connection
                        if ($connection) {
                            $this->streams->detach($key);
                            unset($this->connections[$key]);
                            $connection->disconnect();
                        }
                        $this->logger->error("[server] {$e->getMessage()}");
                        $this->dispatch('error', [$this, $connection, $e]);
                    } catch (CloseException $e) {
                        // Should close
                        if ($connection) {
                            $connection->close($e->getCloseStatus(), $e->getMessage());
                        }
                        $this->logger->error("[server] sss {$e->getMessage()}");
                        $this->dispatch('error', [$this, $connection, $e]);
                    }
                }
                foreach ($this->connections as $connection) {
                    $connection->tick();
                }
                $this->dispatch('tick', [$this]);
            } catch (Exception $e) {
                // Low-level error
                $this->logger->error("[server] {$e->getMessage()}");
                $this->dispatch('error', [$this, null, $e]);
            } catch (Throwable $e) {
                // Crash it
                $this->logger->error("[server] {$e->getMessage()}");
                $this->dispatch('error', [$this, null, $e]);
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
        $this->server->close();
        $this->server = $this->streams = null;
        $this->logger->info('[server] Server disconnected');
    }


    /* ---------- Internal helper methods -------------------------------------------------------------------------- */

    // Create socket server
    protected function createSocketServer(): void
    {
        try {
            $uri = new Uri("{$this->scheme}://0.0.0.0:{$this->port}");
            $this->server = $this->streamFactory->createSocketServer($uri);
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
        try {
            $stream = $socket->accept();
            $name = $stream->getRemoteName();
            $this->streams->attach($stream, $name);
            $connection = new Connection($stream, false, true, $this->isSsl());
            $connection
                ->setLogger($this->logger)
                ->setFrameSize($this->frameSize)
                ->setTimeout($this->timeout)
                ;
            foreach ($this->middlewares as $middleware) {
                $connection->addMiddleware($middleware);
            }
            $request = $this->performHandshake($connection);
            $this->connections[$name] = $connection;
            $this->logger->info("[server] Accepted connection from {$name}.");
            $this->dispatch('connect', [$this, $connection, $request]);
        } catch (Exception $e) {
            if ($connection) {
                $connection->disconnect();
            }
            $error = "Server failed to accept: {$e->getMessage()}";
            throw $e;
        }
    }

    // Detach connections no longer available
    protected function detachUnconnected(): void
    {
        foreach ($this->connections as $key => $connection) {
            if (!$connection->isConnected()) {
                $this->streams->detach($key);
                unset($this->connections[$key]);
                $this->logger->info("[server] Disconnected {$key}.");
                $this->dispatch('disconnect', [$this, $connection]);
            }
        }
    }

    // Perform upgrade handshake on new connections.
    protected function performHandshake(Connection $connection): ServerRequest
    {
        $response = new Response(101);
        $exception = null;

        // Read handshake request
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
            if (strtolower($connectionHeader) != 'upgrade') {
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

            $responseKey = base64_encode(pack('H*', sha1($keyHeader . self::GUID)));
            $response = $response
                ->withHeader('Upgrade', 'websocket')
                ->withHeader('Connection', 'Upgrade')
                ->withHeader('Sec-WebSocket-Accept', $responseKey);
        } catch (HandshakeException $e) {
            $this->logger->warning("[server] {$e->getMessage()}");
            $response = $e->getResponse();
            $exception = $e;
        }

        // Respond to handshake
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
}
