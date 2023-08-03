<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket;

use InvalidArgumentException;
use Phrity\Net\{
    SocketServer,
    SocketStream,
    StreamFactory,
    Uri
};
use Psr\Log\{
    LoggerAwareInterface,
    LoggerInterface,
    NullLogger
};
use Throwable;
use WebSocket\Exception\{
    ConnectionLevelInterface,
    HandshakeException,
    ServerException,
    Exception
};
use WebSocket\Http\{
    ServerRequest,
    Response
};
use WebSocket\Message\Message;
use WebSocket\Middleware\MiddlewareInterface;
use WebSocket\Trait\{
    ListenerTrait,
    OpcodeTrait,
    SendMethodsTrait
};

/**
 * WebSocket\Server class.
 * Entry class for WebSocket server.
 */
class Server implements LoggerAwareInterface
{
    use ListenerTrait;
    use OpcodeTrait;
    use SendMethodsTrait;

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
     */
    public function __construct(int $port = 8000, bool $ssl = false)
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
     * @return string String representation.
     */
    public function __toString(): string
    {
        return sprintf("Server(%s)", "{$this->scheme}://0.0.0.0:{$this->port}" ?: 'closed');
    }


    /* ---------- Configuration ------------------------------------------------------------------------------------ */

    /**
     * Set stream factory to use.
     * @param StreamFactory $streamFactory.
     */
    public function setStreamFactory(StreamFactory $streamFactory): self
    {
        $this->streamFactory = $streamFactory;
        return $this;
    }

    /**
     * Set logger.
     * @param Psr\Log\LoggerInterface $logger Logger implementation
     * @return self.
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
     * @param int $timeout Timeout in seconds.
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
     * @return int Timeout in seconds.
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Set frame size.
     * @param int $frameSize Frame size in bytes.
     * @return self.
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
     * @return int Frame size in bytes.
     */
    public function getFrameSize(): int
    {
        return $this->frameSize;
    }

    /**
     * Get socket port number.
     * @return int port.
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * Get connection scheme.
     * @return string scheme.
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * If server is running (accepting connections and messages).
     * @return bool.
     */
    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * Number of currently connected clients.
     * @return int Connection count.
     */
    public function getConnectionCount(): int
    {
        return count($this->connections);
    }

    /**
     * Add a middleware.
     * @param MiddlewareInterface $middleware
     * @return self.
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
     * @param Message $message Message to send.
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


    /* ---------- Server operations -------------------------------------------------------------------------------- */

    /**
     * Start server listener.
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
                    } catch (ConnectionLevelInterface $t) {
                        $this->logger->error("[server] {$t->getMessage()}");
                        $this->dispatch('error', [$this, $connection, $t]);
                    }
                }
                $this->dispatch('tick', [$this]);
            } catch (Exception $t) {
                $this->logger->error("[server] {$t->getMessage()}");
                $this->dispatch('error', [$this, null, $t]);
            } catch (Throwable $t) {
                $this->logger->error("[server] {$t->getMessage()}");
                $this->disconnect();
                throw $t;
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
        } catch (Throwable $t) {
            $error = "Server failed to start: {$t->getMessage()}";
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
            $connection = new Connection($stream, false, true);
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
        } catch (Throwable $t) {
            if ($connection) {
                $connection->disconnect();
            }
            $error = "Server failed to accept: {$t->getMessage()}";
            throw new ServerException($error);
        }
    }

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
        $connection->pushHttp($response);
        if ($exception) {
            throw $exception;
        }

        $this->logger->debug("[server] Handshake on {$request->getUri()->getPath()}");
        $connection->setHandshakeRequest($request);
        $connection->setHandshakeResponse($response);
        return $request;
    }
}
