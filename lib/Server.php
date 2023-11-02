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
    Factory,
    Message
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
        'filter'        => ['text', 'binary'], // @deprecated
        'fragment_size' => 4096,
        'logger'        => null,
        'masked'        => false,
        'port'          => 8000,
        'return_obj'    => false, // @deprecated
        'schema'        => 'tcp',
        'timeout'       => null,
    ];

    private $streamFactory;
    private $messageFactory;
    private $port;
    private $listening;
    private $handshakeRequest;
    private $connection;
    private $options = [];
    private $last_opcode;


    /* ---------- Magic methods ------------------------------------------------------------------------------------ */

    /**
     * @param array $options
     *   Associative array containing:
     *   - filter:        Array of opcodes to handle. Default: ['text', 'binary'].
     *   - fragment_size: Set framgemnt size.  Default: 4096
     *   - logger:        PSR-3 compatible logger.  Default NullLogger.
     *   - port:          Chose port for listening.  Default 8000.
     *   - return_obj:    If receive() function return Message instance.  Default false.
     *   - schema:        Set socket schema (tcp or ssl).
     *   - timeout:       Set the socket timeout in seconds.
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge(self::$default_options, $options);
        $this->port = $this->options['port'];
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
     * Get close status from single connection.
     * @return int|null Close status.
     */
    public function getCloseStatus(): ?int
    {
        return $this->connection ? $this->connection->getCloseStatus() : null;
    }

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
     * @deprecated Will be removed in future version.
     */
    public function getPier(): ?string
    {
        $this->deprecated('getPier() is deprecated and will be removed. Use getRemoteName() instead.');
        return $this->getRemoteName();
    }

    /**
     * Get requested path from last connection.
     * @return string Path.
     * @deprecated Will be removed in future version.
     */
    public function getPath(): string
    {
        $this->deprecated('getPath() is deprecated and will be removed. Use getHandshakeRequest()->getPath() instead.');
        return $this->handshakeRequest->getUri()->getPath();
    }

    /**
     * Get request from last connection.
     * @return array Request.
     * @deprecated Will be removed in future version.
     */
    public function getRequest(): array
    {
        $this->deprecated('getPath() is deprecated and will be removed. Use getHandshakeRequest() instead.');
        return $this->handshakeRequest->getAsArray();
    }

    /**
     * Get headers from last connection.
     * @deprecated Will be removed in future version.
     * @return string|null Headers.
     */
    public function getHeader($header): ?string
    {
        $this->deprecated(
            'getPath() is deprecated and will be removed. Use getHandshakeRequest()->getHeaderLine() instead.'
        );
        return $this->handshakeRequest->getHeaderLine($header) ?: null;
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

    protected function deprecated(string $message): void
    {
        $this->logger->debug("[server] {$message}");
        trigger_error($message, E_USER_DEPRECATED);
    }
}
