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
    Factory,
    Message
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
        'filter'        => ['text', 'binary'], // @deprecated
        'fragment_size' => 4096,
        'headers'       => [],
        'logger'        => null,
        'masked'        => true,
        'origin'        => null, // @deprecated
        'persistent'    => false,
        'return_obj'    => false, // @deprecated
        'timeout'       => 5,
    ];

    private $socket_uri;
    private $connection;
    private $options = [];
    private $listen = false;
    private $last_opcode = null;
    private $streamFactory;
    private $messageFactory;
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
        $this->messageFactory = new Factory();
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
        $this->connection->setOptions(['fragment_size' => $fragment_size]);
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
     * @param string $payload Content as string.
     */
    public function text(string $payload): void
    {
        $this->send($payload);
    }

    /**
     * Send binary message.
     * @param string $payload Content as binary string.
     */
    public function binary(string $payload): void
    {
        $this->send($payload, 'binary');
    }

    /**
     * Send ping.
     * @param string $payload Optional text as string.
     */
    public function ping(string $payload = ''): void
    {
        $this->send($payload, 'ping');
    }

    /**
     * Send unsolicited pong.
     * @param string $payload Optional text as string.
     */
    public function pong(string $payload = ''): void
    {
        $this->send($payload, 'pong');
    }

    /**
     * Tell the socket to close.
     * @param integer $status  http://tools.ietf.org/html/rfc6455#section-7.4
     * @param string  $message A closing message, max 125 bytes.
     */
    public function close(int $status = 1000, string $message = 'ttfn'): void
    {
        if (!$this->isConnected()) {
            return;
        }
        $this->connection->close($status, $message);
    }

    /**
     * Send message.
     * @param Message|string $payload Message to send, as Meessage instance or string.
     * @param string $opcode Opcode to use, default: 'text'.
     * @param bool $masked If message should be masked default: true.
     */
    public function send($payload, string $opcode = 'text', ?bool $masked = null): void
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        if ($payload instanceof Message) {
            $this->connection->pushMessage($payload, $masked);
            return;
        }
        if (!in_array($opcode, array_keys(self::$opcodes))) {
            $warning = "Bad opcode '{$opcode}'.  Try 'text' or 'binary'.";
            $this->logger->warning("[client] {warning}");
            throw new BadOpcodeException($warning);
        }

        $message = $this->messageFactory->create($opcode, $payload);
        $this->connection->pushMessage($message, $masked);
    }

    /**
     * Receive message.
     * Note that this operation will block reading.
     * @return mixed Message, text or null depending on settings.
     */
    public function receive()
    {
        $filter = $this->options['filter'];
        $return_obj = $this->options['return_obj'];
        $return = null;

        if (!$this->isConnected()) {
            $this->connect();
        }

        while (true) {
            $message = $this->connection->pullMessage();
            $opcode = $message->getOpcode();
            if (in_array($opcode, $filter)) {
                $this->last_opcode = $opcode;
                $return = $return_obj ? $message : $message->getContent();
                break;
            } elseif ($opcode == 'close') {
                $this->last_opcode = null;
                $return = $return_obj ? $message : null;
                break;
            }
        }
        return $return;
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
        $this->connection = new Connection($stream, $this->options);
        $this->connection->setLogger($this->logger);

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
     * Get close status on connection.
     * @return int|null Close status.
     */
    public function getCloseStatus(): ?int
    {
        return $this->connection ? $this->connection->getCloseStatus() : null;
    }

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


    /* ---------- Deprecated methods ------------------------------------------------------------------------------- */

    /**
     * Get last received opcode.
     * @return string|null Opcode.
     * @deprecated Will be removed in future version.
     */
    public function getLastOpcode(): ?string
    {
        $this->deprecated('getLastOpcode() is deprecated and will be removed. Check Message instead..');
        return $this->last_opcode;
    }

    /**
     * Get name of remote socket, or null if not connected.
     * @return string|null
     * @deprecated Will be removed in future version, use getPeer() instead.
     */
    public function getPier(): ?string
    {
        $this->deprecated('getPier() is deprecated and will be removed. Use getRemoteName() instead.');
        return $this->getRemoteName();
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

        // Deprecated way of adding origin (use headers instead).
        if (isset($this->options['origin'])) {
            $request = $request->withHeader('origin', $this->options['origin']);
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

    protected function deprecated(string $message): void
    {
        $this->logger->debug("[client] {$message}");
        trigger_error($message, E_USER_DEPRECATED);
    }
}
