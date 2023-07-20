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
    StreamFactory,
    Uri
};
use Psr\Http\Message\UriInterface;
use Psr\Log\{
    LoggerAwareInterface,
    LoggerAwareTrait,
    NullLogger
};
use Throwable;
use WebSocket\Http\{
    Request,
    Response
};
use WebSocket\Message\{
    Message,
    Binary,
    Close,
    Ping,
    Pong,
    Text
};
use WebSocket\Middleware\{
    CloseHandler,
    PingResponder
};

/**
 * WebSocket\Client class.
 * Entry class for WebSocket client.
 */
class Client implements LoggerAwareInterface
{
    use LoggerAwareTrait; // provides setLogger(LoggerInterface $logger)
    use OpcodeTrait;

    // Default options
    protected static $default_options = [
        'context'       => null,
        'fragment_size' => 4096,
        'headers'       => [],
        'logger'        => null,
        'masked'        => true,
        'persistent'    => false,
        'timeout'       => 5,
    ];

    private $socket_uri;
    private $connection;
    private $options = [];
    private $listen = false;
    private $last_opcode = null;
    private $streamFactory;
    private $handshakeResponse;


    /* ---------- Magic methods ------------------------------------------------------------------------------------ */

    /**
     * @param UriInterface|string $uri A ws/wss-URI
     * @param array $options
     *   Associative array containing:
     *   - context:       Set the stream context. Default: empty context
     *   - timeout:       Set the socket timeout in seconds.  Default: 5
     *   - fragment_size: Set framgemnt size.  Default: 4096
     *   - headers:       Associative array of headers to set/override.
     */
    public function __construct($uri, array $options = [])
    {
        $this->socket_uri = $this->parseUri($uri);
        $this->options = array_merge(self::$default_options, $options);
        $this->setLogger($this->options['logger'] ?: new NullLogger());
        $this->setStreamFactory(new StreamFactory());
    }

    /**
     * Get string representation of instance.
     * @return string String representation.
     */
    public function __toString(): string
    {
        return sprintf(
            "%s(%s)",
            get_class($this),
            $this->getName() ?: 'closed'
        );
    }


    /* ---------- Configuration ------------------------------------------------------------------------------------ */

    /**
     * Set stream factory to use.
     * @param StreamFactory $streamFactory.
     */
    public function setStreamFactory(StreamFactory $streamFactory): void
    {
        $this->streamFactory = $streamFactory;
    }

    /**
     * Set timeout.
     * @param int $timeout Timeout in seconds.
     */
    public function setTimeout(int $timeout): void
    {
        $this->options['timeout'] = $timeout;
        if (!$this->isConnected()) {
            return;
        }
        $this->connection->setTimeout($timeout);
    }

    /**
     * Set fragmentation size.
     * @param int $fragment_size Fragment size in bytes.
     * @return self.
     */
    public function setFragmentSize(int $fragment_size): self
    {
        $this->options['fragment_size'] = $fragment_size;
        if (!$this->connection) {
            return $this;
        }
        $this->connection->setFrameSize($fragment_size);
        return $this;
    }

    /**
     * Get fragmentation size.
     * @return int $fragment_size Fragment size in bytes.
     */
    public function getFragmentSize(): int
    {
        return $this->options['fragment_size'];
    }


    /* ---------- Messaging operations ----------------------------------------------------------------------------- */

    /**
     * Send text message.
     * @param string $message Content as string.
     * @param bool $masked If message should be masked
     */
    public function text(string $message, ?bool $masked = null): void
    {
        $this->send(new Text($message), $masked);
    }

    /**
     * Send binary message.
     * @param string $message Content as binary string.
     * @param bool $masked If message should be masked
     */
    public function binary(string $message, ?bool $masked = null): void
    {
        $this->send(new Binary($message), $masked);
    }

    /**
     * Send ping.
     * @param string $message Optional text as string.
     * @param bool $masked If message should be masked
     */
    public function ping(string $message = '', ?bool $masked = null): void
    {
        $this->send(new Ping($message), $masked);
    }

    /**
     * Send unsolicited pong.
     * @param string $message Optional text as string.
     * @param bool $masked If message should be masked
     */
    public function pong(string $message = '', ?bool $masked = null): void
    {
        $this->send(new Pong($message), $masked);
    }

    /**
     * Tell the socket to close.
     * @param integer $status  http://tools.ietf.org/html/rfc6455#section-7.4
     * @param string  $message A closing message, max 125 bytes.
     * @param bool $masked If message should be masked
     */
    public function close(int $status = 1000, string $message = 'ttfn', ?bool $masked = null): void
    {
        if (!$this->isConnected()) {
            return;
        }
        $this->connection->close($status, $message);
    }

    /**
     * Send message.
     * @param Message $message Message to send.
     * @param bool $masked If message should be masked
     */
    public function send(Message $message, ?bool $masked = null): void
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        $this->connection->pushMessage($message, $masked);
    }

    /**
     * Receive message.
     * Note that this operation will block reading.
     * @return Message|null
     */
    public function receive(): ?Message
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        return $this->connection->pullMessage();
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
     * Connect to server and perform upgrade.
     * @throws ConnectionException On failed connection
     */
    public function connect(): void
    {
        $this->disconnect();

        $host_uri = (new Uri())
            ->withScheme($this->socket_uri->getScheme() == 'wss' ? 'ssl' : 'tcp')
            ->withHost($this->socket_uri->getHost(Uri::IDNA))
            ->withPort($this->socket_uri->getPort(Uri::REQUIRE_PORT));

        $context = $this->parseContext();
        $persistent = $this->options['persistent'] === true;
        $stream = null;

        try {
            $client = $this->streamFactory->createSocketClient($host_uri);
            $client->setPersistent($persistent);
            $client->setTimeout($this->options['timeout']);
            $client->setContext($context);
            $stream = $client->connect();
        } catch (Throwable $e) {
            $error = "Could not open socket to \"{$host_uri}\": Server is closed.";
            $this->logger->error("[client] {$error}", []);
            throw new ConnectionException($error, ConnectionException::CLIENT_CONNECT_ERR, [], $e);
        }
        $this->connection = new Connection($stream, true, false);
        $this->connection->setTimeout($this->options['timeout']);
        $this->connection->setLogger($this->logger);
        $this->connection->addMiddleware(new CloseHandler());
        $this->connection->addMiddleware(new PingResponder());

        if (!$this->isConnected()) {
            $error = "Invalid stream on \"{$host_uri}\".";
            $this->logger->error("[client] {$error}");
            throw new ConnectionException($error, ConnectionException::CLIENT_CONNECT_ERR);
        }

        if (!$persistent || $stream->tell() == 0) {
            $this->handshakeResponse = $this->performHandshake($host_uri);
        }

        $this->logger->info("[client] Client connected to {$this->socket_uri}");
    }

    /**
     * Disconnect from server.
     */
    public function disconnect(): void
    {
        if ($this->isConnected()) {
            $this->connection->disconnect();
            $this->logger->info('[client] Client disconnected');
        }
    }


    /* ---------- Connection state --------------------------------------------------------------------------------- */

    /**
     * Get name of local socket, or null if not connected.
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->isConnected() ? $this->connection->getName() : null;
    }

    /**
     * Get name of remote socket, or null if not connected.
     * @return string|null
     */
    public function getRemoteName(): ?string
    {
        return $this->isConnected() ? $this->connection->getRemoteName() : null;
    }

    /**
     * Get Response for handshake procedure.
     * @return Response|null Handshake.
     */
    public function getHandshakeResponse(): ?Response
    {
        return $this->connection ? $this->handshakeResponse : null;
    }


    /* ---------- Internal helper methods -------------------------------------------------------------------------- */

    /**
     * Perform upgrade handshake on new connections.
     * @throws ConnectionException On failed handshake
     */
    protected function performHandshake($host_uri): Response
    {
        $http_uri = (new Uri())
            ->withPath($this->socket_uri->getPath(), Uri::ABSOLUTE_PATH)
            ->withQuery($this->socket_uri->getQuery());

        // Generate the WebSocket key.
        $key = $this->generateKey();

        $request = new Request('GET', $http_uri);

        $request = $request
            ->withHeader('Host', $host_uri->getAuthority())
            ->withHeader('User-Agent', 'websocket-client-php')
            ->withHeader('Connection', 'Upgrade')
            ->withHeader('Upgrade', 'websocket')
            ->withHeader('Sec-WebSocket-Key', $key)
            ->withHeader('Sec-WebSocket-Version', '13');

        // Handle basic authentication.
        if ($userinfo = $this->socket_uri->getUserInfo()) {
            $request = $request->withHeader('authorization', 'Basic ' . base64_encode($userinfo));
        }

        // Add and override with headers from options.
        foreach ($this->options['headers'] as $name => $content) {
            $request = $request->withHeader($name, $content);
        }

        try {
            $this->connection->pushHttp($request);
            $response = $this->connection->pullHttp();
        } catch (Throwable $e) {
            $error = 'Client handshake error';
            $this->logger->error("[client] {$error}");
            throw new ConnectionException($error, ConnectionException::CLIENT_HANDSHAKE_ERR);
        }

        if ($response->getStatusCode() != 101) {
            $error = "Invalid status code {$response->getStatusCode()}.";
            $this->logger->error("[client] {$error}");
            throw new ConnectionException($error, ConnectionException::CLIENT_HANDSHAKE_ERR);
        }

        if (empty($response->getHeaderLine('Sec-WebSocket-Accept'))) {
            $error = sprintf(
                "Connection to '%s' failed: Server sent invalid upgrade response.",
                (string)$this->socket_uri
            );
            $this->logger->error("[client] {$error}");
            throw new ConnectionException($error, ConnectionException::CLIENT_HANDSHAKE_ERR);
        }

        $response_key = trim($response->getHeaderLine('Sec-WebSocket-Accept'));
        $expected_key = base64_encode(
            pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11'))
        );

        if ($response_key !== $expected_key) {
            $error = 'Server sent bad upgrade response.';
            $this->logger->error("[client] {$error}");
            throw new ConnectionException($error, ConnectionException::CLIENT_HANDSHAKE_ERR);
        }
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
     * Ensure URI insatnce to use in client.
     * @param UriInterface|string $uri A ws/wss-URI
     * @return Uri
     * @throws BadUriException On invalid URI
     */
    protected function parseUri($uri): UriInterface
    {
        if ($uri instanceof Uri) {
            $uri_instance = $uri;
        } elseif ($uri instanceof UriInterface) {
            $uri_instance = new Uri("{$uri}");
        } elseif (is_string($uri)) {
            try {
                $uri_instance = new Uri($uri);
            } catch (InvalidArgumentException $e) {
                throw new BadUriException("Invalid URI '{$uri}' provided.");
            }
        } else {
            throw new BadUriException("Provided URI must be a UriInterface or string.");
        }
        if (!in_array($uri_instance->getScheme(), ['ws', 'wss'])) {
            throw new BadUriException("Invalid URI scheme, must be 'ws' or 'wss'.");
        }
        if (!$uri_instance->getHost()) {
            throw new BadUriException("Invalid URI host.");
        }
        return $uri_instance;
    }

    /**
     * Ensure context in correct format.
     * @return array
     * @throws InvalidArgumentException On invalid context
     */
    protected function parseContext(): array
    {
        if (empty($this->options['context'])) {
            return [];
        }
        if (is_array($this->options['context'])) {
            return $this->options['context'];
        }
        if (
            is_resource($this->options['context'])
            && get_resource_type($this->options['context']) === 'stream-context'
        ) {
            return stream_context_get_options($this->options['context']);
        }
        $error = "Stream context in \$options['context'] isn't a valid context.";
        $this->logger->error("[client] {$error}");
        throw new InvalidArgumentException($error);
    }
}
