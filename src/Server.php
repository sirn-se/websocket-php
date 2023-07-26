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
use Psr\Log\{
    LoggerAwareInterface,
    LoggerInterface,
    NullLogger
};
use RuntimeException;
use Throwable;
use WebSocket\Http\{
    ServerRequest,
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
 * WebSocket\Server class.
 * Entry class for WebSocket server.
 */
class Server implements LoggerAwareInterface
{
    use OpcodeTrait;

    private const GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    // Default options
    protected static $default_options = [
        'fragment_size' => 4096,
        'logger'        => null,
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
     *   - fragment_size: Set framgemnt size.  Default: 4096
     *   - logger:        PSR-3 compatible logger.  Default NullLogger.
     *   - port:          Chose port for listening.  Default 8000.
     *   - schema:        Set socket schema (tcp or ssl).
     *   - timeout:       Set the socket timeout in seconds.
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge(self::$default_options, $options);
        if (!in_array($this->options['schema'], ['tcp', 'ssl'])) {
            throw new InvalidArgumentException("Invalid schema '{$this->options['schema']}' provided");
        }
        if (!is_int($this->options['port']) || $this->options['port'] < 0 || $this->options['port'] > 65535) {
            throw new InvalidArgumentException("Invalid port '{$this->options['port']}' provided");
        }
        $this->port = (int)$this->options['port'];
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
     * Set logger.
     * @param Psr\Log\LoggerInterface $logger Logger implementation
     * @return self.
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        if ($this->connection) {
            $this->connection->setLogger($this->logger);
        }
        return $this;
    }

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
     */
    public function text(string $message): void
    {
        $this->send(new Text($message));
    }

    /**
     * Send binary message.
     * @param string $message Content as binary string.
     */
    public function binary(string $message): void
    {
        $this->send(new Binary($message));
    }

    /**
     * Send ping.
     * @param string $message Optional text as string.
     */
    public function ping(string $message = ''): void
    {
        $this->send(new Ping($message));
    }

    /**
     * Send unsolicited pong.
     * @param string $message Optional text as string.
     */
    public function pong(string $message = ''): void
    {
        $this->send(new Pong($message));
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
        $this->send(new Close($status, $message));
    }

    /**
     * Send message.
     * @param Message $message Message to send.
     */
    public function send(Message $message): void
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        $this->connection->pushMessage($message);
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
            $this->logger->error("[server] {$error}");
            throw new ConnectionException($error, ConnectionException::SERVER_ACCEPT_ERR, [], $e);
        }

        $this->connection = new Connection($socket, false, true);
        $this->connection->setFrameSize($this->options['fragment_size']);
        if ($this->options['timeout']) {
            $this->connection->setTimeout($this->options['timeout']);
        }
        $this->connection->setLogger($this->logger);
        $this->connection->addMiddleware(new CloseHandler());
        $this->connection->addMiddleware(new PingResponder());

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


        try {
            $uri = new Uri("{$this->options['schema']}://0.0.0.0:{$this->port}");
            $this->listening = $this->streamFactory->createSocketServer($uri);
        } catch (Throwable $e) {
            $error = "Could not connect on port {$this->port}: {$e->getMessage()}";
            $this->logger->error("[server] {$error}");
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
     * @return ServerRequest|null Handshake.
     */
    public function getHandshakeRequest(): ?ServerRequest
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
        } catch (Throwable $e) {
            $error = 'Server handshake error';
            $this->logger->error("[server] {$error}");
            throw new ConnectionException($error, ConnectionException::SERVER_HANDSHAKE_ERR);
        }

        $connectionHeader = trim($request->getHeaderLine('Connection'));
        if (strtolower($connectionHeader) != 'upgrade') {
            $error = "Handshake request with invalid Connection header: '{$connectionHeader}'";
            $this->logger->error("[server] {$error}");
            throw new ConnectionException($error, ConnectionException::SERVER_HANDSHAKE_ERR);
        }
        $upgradeHeader = trim($request->getHeaderLine('Upgrade'));
        if (strtolower($upgradeHeader) != 'websocket') {
            $error = "Handshake request with invalid Upgrade header: '{$upgradeHeader}'";
            $this->logger->error("[server] {$error}");
            throw new ConnectionException($error, ConnectionException::SERVER_HANDSHAKE_ERR);
        }
        $keyHeader = trim($request->getHeaderLine('Sec-WebSocket-Key'));
        if (empty($keyHeader)) {
            $error = "Handshake request with invalid Sec-WebSocket-Key header: '{$keyHeader}'";
            $this->logger->error("[server] {$error}");
            throw new ConnectionException($error, ConnectionException::SERVER_HANDSHAKE_ERR);
        }
        if (strlen(base64_decode($keyHeader)) != 16) {
            $error = "Handshake request with invalid Sec-WebSocket-Key header: '{$keyHeader}'";
            $this->logger->error("[server] {$error}");
            throw new ConnectionException($error, ConnectionException::SERVER_HANDSHAKE_ERR);
        }

        $responseKey = base64_encode(pack('H*', sha1($keyHeader . self::GUID)));
        try {
            $response = $response
                ->withHeader('Upgrade', 'websocket')
                ->withHeader('Connection', 'Upgrade')
                ->withHeader('Sec-WebSocket-Accept', $responseKey);
            $connection->pushHttp($response);
        } catch (Throwable $e) {
            $error = 'Server handshake error';
            $this->logger->error("[server] {$error}");
            throw new ConnectionException($error, ConnectionException::SERVER_HANDSHAKE_ERR);
        }

        $this->logger->debug("[server] Handshake on {$request->getUri()->getPath()}");
        $this->handshakeRequest = $request;
    }
}
