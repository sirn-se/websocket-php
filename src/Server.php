<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket;

use Phrity\Net\{
    StreamFactory,
    Uri
};
use Psr\Log\{
    LoggerAwareInterface,
    LoggerAwareTrait,
    NullLogger
};
use RuntimeException;
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

/**
 * WebSocket\Server class.
 * Entry class for WebSocket server.
 */
class Server implements LoggerAwareInterface
{
    use LoggerAwareTrait; // Provides setLogger(LoggerInterface $logger)
    use OpcodeTrait;

    // Default options
    protected static $default_options = [
        'fragment_size' => 4096,
        'logger'        => null,
        'masked'        => false,
        'port'          => 8000,
        'schema'        => 'tcp',
        'timeout'       => null,
    ];

    private $streamFactory;
    private $port;
    private $listening;
    private $handshakeRequest;
    private $connection;
    private $options = [];


    /* ---------- Magic methods ------------------------------------------------------------------------------------ */

    /**
     * @param array $options
     *   Associative array containing:
     *   - filter:        Array of opcodes to handle. Default: ['text', 'binary'].
     *   - fragment_size: Set framgemnt size.  Default: 4096
     *   - logger:        PSR-3 compatible logger.  Default NullLogger.
     *   - port:          Chose port for listening.  Default 8000.
     *   - schema:        Set socket schema (tcp or ssl).
     *   - timeout:       Set the socket timeout in seconds.
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge(self::$default_options, $options);
        $this->port = $this->options['port'];
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
    public function setStreamFactory(StreamFactory $streamFactory)
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
     * If Server has active connections.
     * @return bool True if active connection.
     */
    public function isConnected(): bool
    {
        return $this->connection && $this->connection->isConnected();
    }

    // Connect when read/write operation is performed.
    public function connect(): void
    {
        try {
            if (isset($this->options['timeout'])) {
                $socket = $this->listening->accept($this->options['timeout']);
            } else {
                $socket = $this->listening->accept();
            }
            if (!$socket) {
                throw new RuntimeException('No socket');
            }
        } catch (RuntimeException $e) {
            $error = "Server failed to connect. {$e->getMessage()}";
            $this->logger->error($error);
            throw new ConnectionException($error, ConnectionException::SERVER_ACCEPT_ERR, [], $e);
        }

        $this->connection = new Connection($socket, $this->options);
        $this->connection->setLogger($this->logger);

        $this->logger->info("Client has connected to port {port}", [
            'port' => $this->port,
        ]);
        $this->performHandshake($this->connection);
    }

    /**
     * Disconnect client.
     */
    public function disconnect(): void
    {
        if ($this->isConnected()) {
            $this->connection->disconnect();
            $this->logger->info('[server] Server disconnected');
        }
    }

    /**
     * Accept a single incoming request.
     * Note that this operation will block accepting additional requests.
     * @return bool True if listening.
     */
    public function accept(): bool
    {
        $this->disconnect();
        $exception = null;

        do {
            try {
                $uri = new Uri("{$this->options['schema']}://0.0.0.0:{$this->port}");
                $this->listening = $this->streamFactory->createSocketServer($uri);
            } catch (RuntimeException $e) {
                $this->logger->error("Could not connect on port {$this->port}: {$e->getMessage()}");
                $exception = $e;
            }
        } while (is_null($this->listening) && $this->port++ < 10000);

        if (!$this->listening) {
            $error = "Could not open listening socket: {$exception->getMessage()}";
            $this->logger->error($error);
            throw new ConnectionException($error, ConnectionException::SERVER_SOCKET_ERR);
        }

        $this->logger->info("Server listening to port {$uri}");

        return (bool)$this->listening;
    }


    /* ---------- Connection state --------------------------------------------------------------------------------- */

    /**
     * Get name of local socket from single connection.
     * @return string|null Name of local socket.
     */
    public function getName(): ?string
    {
        return $this->isConnected() ? $this->connection->getName() : null;
    }

    /**
     * Get name of remote socket from single connection.
     * @return string|null Name of remote socket.
     */
    public function getRemoteName(): ?string
    {
        return $this->isConnected() ? $this->connection->getRemoteName() : null;
    }

    /**
     * Get current port.
     * @return int port.
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * Get Request for handshake procedure.
     * @return Request|null Handshake.
     */
    public function getHandshakeRequest(): ?Request
    {
        return $this->connection ? $this->handshakeRequest : null;
    }


    /* ---------- Internal helper methods -------------------------------------------------------------------------- */

    // Perform upgrade handshake on new connections.
    protected function performHandshake(Connection $connection): void
    {
        $response = new Response(101);

        try {
            $request = $connection->pullHttp();
        } catch (RuntimeException $e) {
            $error = 'Client handshake error';
            $this->logger->error($error);
            throw new ConnectionException($error, ConnectionException::SERVER_HANDSHAKE_ERR);
        }

        $key = trim((string)$request->getHeaderLine('Sec-WebSocket-Key'));
        if (empty($key)) {
            $error = sprintf(
                "Client had no Key in upgrade request: %s",
                json_encode($request->getHeaders())
            );
            $this->logger->error($error);
            throw new ConnectionException($error, ConnectionException::SERVER_HANDSHAKE_ERR);
        }

        /// @todo Validate key length and base 64...
        $response_key = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

        $response = $response
            ->withHeader('Upgrade', 'websocket')
            ->withHeader('Connection', 'Upgrade')
            ->withHeader('Sec-WebSocket-Accept', $response_key);
        $connection->pushHttp($response);

        $this->logger->debug("Handshake on {$request->getUri()->getPath()}");

        $this->handshakeRequest = $request;
    }
}
