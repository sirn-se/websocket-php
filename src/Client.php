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
    StreamCollection,
    StreamFactory,
    Uri
};
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface,
    UriInterface,
};
use Psr\Log\{
    LoggerAwareInterface,
    LoggerInterface,
};
use Stringable;
use Throwable;
use WebSocket\Exception\{
    BadUriException,
    ClientException,
    CloseException,
    ConnectionLevelInterface,
    ExceptionInterface,
    HandshakeException,
    MessageLevelInterface,
    ReconnectException,
};
use WebSocket\Http\DefaultHttpFactory;
use WebSocket\Message\Message;
use WebSocket\Middleware\MiddlewareInterface;
use WebSocket\Trait\{
    ListenerTrait,
    LoggerAwareTrait,
    SendMethodsTrait,
    StringableTrait
};

/**
 * WebSocket\Client class.
 * Entry class for WebSocket client.
 */
class Client implements LoggerAwareInterface, Stringable
{
    /** @use ListenerTrait<Client> */
    use ListenerTrait;
    use LoggerAwareTrait;
    use SendMethodsTrait;
    use StringableTrait;

    // Settings
    /** @var int<0, max>|float $timeout */
    private int|float $timeout = 60;
    /** @var int<1, max> $frameSize */
    private int $frameSize = 4096;
    private bool $persistent = false;
    private Context $context;
    /** @var array<string, mixed> $headers */
    private array $headers = [];

    // Internal resources
    private StreamFactory $streamFactory;
    private Uri $socketUri;
    private Connection|null $connection = null;
    /** @var array<MiddlewareInterface> $middlewares */
    private array $middlewares = [];
    private StreamCollection|null $streams = null;
    private bool $running = false;
    private HttpFactory $httpFactory;


    /* ---------- Magic methods ------------------------------------------------------------------------------------ */

    /**
     * @param UriInterface|string $uri A ws/wss-URI
     */
    public function __construct(UriInterface|string $uri)
    {
        $this->socketUri = $this->parseUri($uri);
        $this->initLogger();
        $this->context = new Context();
        $this->setStreamFactory(new StreamFactory());
        $this->httpFactory = new DefaultHttpFactory();
    }

    /**
     * Get string representation of instance.
     * @return string String representation
     */
    public function __toString(): string
    {
        return $this->stringable('%s', $this->connection ? $this->socketUri->__toString() : 'closed');
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
        if ($this->connection) {
            $this->connection->setLogger($this->logger);
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
        if ($this->connection) {
            $this->connection->setTimeout($timeout);
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
     * @param int<1, max> $frameSize Max frame payload size in bytes
     * @return self
     * @throws InvalidArgumentException If invalid frameSize provided
     */
    public function setFrameSize(int $frameSize): self
    {
        if ($frameSize < 1) {
            throw new InvalidArgumentException("Invalid frameSize '{$frameSize}' provided");
        }
        $this->frameSize = $frameSize;
        if ($this->connection) {
            $this->connection->setFrameSize($frameSize);
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
     * Set connection persistence.
     * @param bool $persistent True for persistent connection.
     * @return self
     */
    public function setPersistent(bool $persistent): self
    {
        $this->persistent = $persistent;
        return $this;
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
            trigger_error('Calling Client.setContext with array is deprecated, use Context class.', E_USER_DEPRECATED);
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
     * Add header for handshake.
     * @param string $name Header name
     * @param string $content Header content
     * @return self
     */
    public function addHeader(string $name, string $content): self
    {
        $this->headers[$name] = $content;
        return $this;
    }

    /**
     * Add a middleware.
     * @param MiddlewareInterface $middleware
     * @return self
     */
    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        if ($this->connection) {
            $this->connection->addMiddleware($middleware);
        }
        return $this;
    }


    /* ---------- Messaging operations ----------------------------------------------------------------------------- */

    /**
     * Send message.
     * @template T of Message
     * @param T $message
     * @return T
     */
    public function send(Message $message): Message
    {
        return $this->connection()->pushMessage($message);
    }

    /**
     * Receive message.
     * Note that this operation will block reading.
     * @return Message
     */
    public function receive(): Message
    {
        return $this->connection()->pullMessage();
    }


    /* ---------- Listener operations ------------------------------------------------------------------------------ */

    /**
     * Start client listener.
     * @throws ExceptionInterface On high level error
     * @throws Throwable On low level error
     */
    public function start(int|float|null $timeout = null): void
    {
        // Check if running
        if ($this->running) {
            $this->logger->warning("[client] Client is already running");
            return;
        }
        $this->running = true;
        $reconnect = false;
        $this->logger->info("[client] Client is running");

        $connection = $this->connection();

        // Run handler
        while ($this->running) {
            /** @var StreamCollection */
            $streams = $this->streams;
            try {
                // Get streams with readable content
                $readables = $streams->waitRead($timeout ?? $this->timeout);
                foreach ($readables as $key => $readable) {
                    try {
                        // Read from connection
                        $message = $connection->pullMessage();
                        $this->dispatch($message->getOpcode(), [$this, $connection, $message]);
                    } catch (MessageLevelInterface $e) {
                        // Error, but keep connection open
                        $this->logger->error("[client] {$e->getMessage()}", ['exception' => $e]);
                        $this->dispatch('error', [$this, $connection, $e]);
                    } catch (ConnectionLevelInterface $e) {
                        // Error, disconnect connection
                        $this->disconnect();
                        $this->logger->error("[client] {$e->getMessage()}", ['exception' => $e]);
                        $this->dispatch('error', [$this, $connection, $e]);
                    }
                }
                if (!$connection->isConnected()) {
                    $this->running = false;
                }
                $connection->tick();
                $this->dispatch('tick', [$this]);
            } catch (CloseException $e) {
                // Close connection
                $connection->close($e->getCloseStatus(), $e->getMessage());
                $this->logger->error("[server] {$e->getMessage()}", ['exception' => $e]);
                $this->dispatch('error', [$this, $connection, $e]);
            } catch (ReconnectException $e) {
                // Reconnect connection
                $reconnect = true;
                if ($uri = $e->getUri()) {
                    $this->socketUri = $uri;
                }
                $connection->close();
                $this->logger->error("[server] {$e->getMessage()}", ['exception' => $e]);
                $this->dispatch('error', [$this, $connection, $e]);
            } catch (ExceptionInterface $e) {
                $this->disconnect();
                $this->running = false;

                // Low-level error
                $this->logger->error("[client] {$e->getMessage()}", ['exception' => $e]);
                $this->dispatch('error', [$this, null, $e]);
            } catch (Throwable $e) {
                $this->disconnect();
                $this->running = false;

                // Crash it
                $this->logger->error("[client] {$e->getMessage()}", ['exception' => $e]);
                throw $e;
            }
            gc_collect_cycles(); // Collect garbage

            if ($reconnect && !$connection->isConnected()) {
                $reconnect = false;
                $this->running = true;
            }
        }
    }

    /**
     * Stop client listener (resumable).
     */
    public function stop(): void
    {
        $this->running = false;
        $this->logger->info("[client] Client is stopped");
    }

    /**
     * If client is running (accepting messages).
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->running;
    }


    /* ---------- Connection management ---------------------------------------------------------------------------- */

    /**
     * If Client has active connection.
     * @return bool True if active connection.
     */
    public function isConnected(): bool
    {
        return $this->connection && $this->connection->isConnected();
    }

    /**
     * If Client is readable.
     * @return bool
     */
    public function isReadable(): bool
    {
        return $this->connection && $this->connection->isReadable();
    }

    /**
     * If Client is writable.
     * @return bool
     */
    public function isWritable(): bool
    {
        return $this->connection && $this->connection->isWritable();
    }


    /**
     * Connect to server and perform upgrade.
     * @throws ClientException On failed connection
     */
    public function connect(): void
    {
        $this->disconnect();
        $this->streams = $this->streamFactory->createStreamCollection();

        $hostUri = (new Uri())
            ->withScheme(match ($this->socketUri->getScheme()) {
                'ws', 'http' => 'tcp',
                'wss', 'https' => 'ssl',
                default => throw new ClientException("Invalid socket scheme: {$this->socketUri->getScheme()}")
            })
            ->withHost($this->socketUri->getHost(Uri::IDN_ENCODE))
            ->withPort($this->socketUri->getPort(Uri::REQUIRE_PORT));

        $stream = null;

        try {
            $client = $this->streamFactory->createSocketClient($hostUri, $this->context);
            $client->setPersistent($this->persistent);
            $client->setTimeout($this->timeout);
            $stream = $client->connect();
        } catch (Throwable $e) {
            $error = "Could not open socket to \"{$hostUri}\": {$e->getMessage()}";
            $this->logger->error("[client] {$error}", ['exception' => $e]);
            throw new ClientException($error);
        }
        $name = $stream->getRemoteName();
        $this->streams->attach($stream, $name);
        $this->connection = new Connection(
            $stream,
            true,
            false,
            $hostUri->getScheme() === 'ssl',
            $this->httpFactory
        );
        $this->connection->setFrameSize($this->frameSize);
        $this->connection->setTimeout($this->timeout);
        $this->connection->setLogger($this->logger);
        foreach ($this->middlewares as $middleware) {
            $this->connection->addMiddleware($middleware);
        }

        if (!$this->isConnected()) {
            $error = "Invalid stream on \"{$hostUri}\".";
            $this->logger->error("[client] {$error}");
            throw new ClientException($error);
        }
        try {
            if (!$this->persistent || $stream->tell() == 0) {
                /** @throws ReconnectException */
                $response = $this->performHandshake($this->socketUri, $this->connection);
            }
        } catch (ReconnectException $e) {
            $this->logger->info("[client] {$e->getMessage()}", ['exception' => $e]);
            if ($uri = $e->getUri()) {
                $this->socketUri = $uri;
            }
            $this->connect();
            return;
        }
        $this->logger->info("[client] Client connected to {$this->socketUri}");
        $this->dispatch('handshake', [
            $this,
            $this->connection,
            $this->connection->getHandshakeRequest(),
            $this->connection->getHandshakeResponse(),
        ]);
        $this->dispatch('connect', [$this, $this->connection, $this->connection?->getHandshakeResponse()]);
    }

    /**
     * Disconnect from server.
     */
    public function disconnect(): void
    {
        if ($this->connection && $this->isConnected()) {
            $this->connection->disconnect();
            $this->logger->info('[client] Client disconnected');
            $this->dispatch('disconnect', [$this, $this->connection]);
        }
    }


    /* ---------- Connection wrapper methods ----------------------------------------------------------------------- */

    /**
     * Get name of local socket, or null if not connected.
     * @return string|null
     */
    public function getName(): string|null
    {
        return $this->isConnected() ? $this->connection?->getName() : null;
    }

    /**
     * Get name of remote socket, or null if not connected.
     * @return string|null
     */
    public function getRemoteName(): string|null
    {
        return $this->isConnected() ? $this->connection?->getRemoteName() : null;
    }

    /**
     * Get meta value on connection.
     * @param string $key Meta key
     * @return mixed Meta value
     * @deprecated Will be removed in v4
     */
    public function getMeta(string $key): mixed
    {
        trigger_error('Client.getMeta is deprecated and will be removed in v4.', E_USER_DEPRECATED);
        return $this->isConnected() ? $this->connection?->getMeta($key) : null;
    }

    /**
     * Get Response for handshake procedure.
     * @return ResponseInterface|null Handshake.
     */
    public function getHandshakeResponse(): ResponseInterface|null
    {
        return $this->connection ? $this->connection->getHandshakeResponse() : null;
    }


    /* ---------- Internal helper methods -------------------------------------------------------------------------- */

    /**
     * Perform upgrade handshake on new connections.
     * @throws HandshakeException On failed handshake
     */
    protected function performHandshake(Uri $uri, Connection $connection): ResponseInterface
    {
        // Generate the WebSocket key.
        $key = $this->generateKey();

        $request = $this->httpFactory->createRequest('GET', $uri);

        $request = $request
            ->withHeader('User-Agent', 'websocket-client-php')
            ->withHeader('Connection', 'Upgrade')
            ->withHeader('Upgrade', 'websocket')
            ->withHeader('Sec-WebSocket-Key', $key)
            ->withHeader('Sec-WebSocket-Version', '13');

        // Handle basic authentication.
        if ($userinfo = $uri->getUserInfo(Uri::URI_DECODE)) {
            $request = $request->withHeader('Authorization', 'Basic ' . base64_encode($userinfo));
        }

        // Add and override with headers.
        foreach ($this->headers as $name => $content) {
            $request = $request->withHeader($name, $content);
        }

        try {
            /** @var RequestInterface */
            $request = $connection->pushHttp($request);
            /** @var ResponseInterface */
            $response = $connection->pullHttp();

            if ($response->getStatusCode() != 101) {
                throw new HandshakeException("Invalid status code {$response->getStatusCode()}.", $response);
            }

            if (empty($response->getHeaderLine('Sec-WebSocket-Accept'))) {
                throw new HandshakeException(
                    "Connection to '{$uri}' failed: Server sent invalid upgrade response.",
                    $response
                );
            }

            $responseKey = trim($response->getHeaderLine('Sec-WebSocket-Accept'));
            $expectedKey = base64_encode(
                pack('H*', sha1($key . Constant::GUID))
            );

            if ($responseKey !== $expectedKey) {
                throw new HandshakeException("Server sent bad upgrade response.", $response);
            }
        } catch (HandshakeException $e) {
            $this->logger->error("[client] {$e->getMessage()}", ['exception' => $e]);
            throw $e;
        }

        $this->logger->debug("[client] Handshake on {$uri->getPath()}");
        $connection->setHandshakeRequest($request);
        $connection->setHandshakeResponse($response);

        return $response;
    }

    /**
     * Generate a random string for WebSocket key.
     * @return string Random string
     */
    protected function generateKey(): string
    {
        $key = '';
        for ($i = 0; $i < 16; $i++) {
            $key .= chr(rand(33, 126));
        }
        return base64_encode($key);
    }

    /**
     * Ensure URI instance to use in client.
     * @param UriInterface|string $uri A ws/wss-URI
     * @return Uri
     * @throws BadUriException On invalid URI
     */
    protected function parseUri(UriInterface|string $uri): Uri
    {
        try {
            if ($uri instanceof Uri) {
                $uriInstance = $uri;
            } elseif ($uri instanceof UriInterface) {
                $uriInstance = new Uri("{$uri}");
            } else {
                $uriInstance = new Uri($uri);
            }
        } catch (InvalidArgumentException $e) {
            throw new BadUriException("Invalid URI '{$uri}' provided.");
        }


        if (!in_array($uriInstance->getScheme(), ['ws', 'wss'])) {
            throw new BadUriException("Invalid URI scheme, must be 'ws' or 'wss'.");
        }
        if (!$uriInstance->getHost()) {
            throw new BadUriException("Invalid URI host.");
        }
        return $uriInstance;
    }

    protected function connection(): Connection
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        /** @var Connection */
        $connection = $this->connection;
        return $connection;
    }
}
