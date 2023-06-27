<?php

/**
 * Copyright (C) 2014-2022 Textalk/Abicart and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/Textalk/websocket-php/master/COPYING
 */

namespace WebSocket;

use Closure;
use ErrorException;
use Phrity\Util\ErrorHandler;
use Phrity\Net\StreamFactory;
use Phrity\Net\Uri;
use Psr\Log\{
    LoggerAwareInterface,
    LoggerAwareTrait,
    LoggerInterface,
    NullLogger
};
use Throwable;
use WebSocket\Message\Factory;

class Server implements LoggerAwareInterface
{
    use LoggerAwareTrait; // Provides setLogger(LoggerInterface $logger)
    use OpcodeTrait;

    private $stream_factory;


    // Default options
    protected static $default_options = [
        'filter'        => ['text', 'binary'],
        'fragment_size' => 4096,
        'logger'        => null,
        'masked'        => false,
        'port'          => 8000,
        'return_obj'    => false,
        'schema'        => 'tcp',
        'timeout'       => null,
    ];

    protected $port;
    protected $listening;
    protected $request;
    protected $request_path;
    private $connections = [];
    private $options = [];
    private $listen = false;
    private $last_opcode;


    /* ---------- Magic methods ------------------------------------------------------ */

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
        $this->options = array_merge(self::$default_options, [
            'logger' => new NullLogger(),
        ], $options);
        $this->port = $this->options['port'];
        $this->setLogger($this->options['logger']);
        $this->setStreamFactory(new StreamFactory());
    }

    public function setStreamFactory(StreamFactory $stream_factory)
    {
        $this->stream_factory = $stream_factory;
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


    /* ---------- Server operations -------------------------------------------------- */

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
                $this->listening = $this->stream_factory->createSocketServer($uri);
            } catch (\RuntimeException $e) {
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


    /* ---------- Server option functions -------------------------------------------- */

    /**
     * Get current port.
     * @return int port.
     */
    public function getPort(): int
    {
        return $this->port;
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
        foreach ($this->connections as $connection) {
            $connection->setTimeout($timeout);
            $connection->setOptions($this->options);
        }
    }

    /**
     * Set fragmentation size.
     * @param int $fragment_size Fragment size in bytes.
     * @return self.
     */
    public function setFragmentSize(int $fragment_size): self
    {
        $this->options['fragment_size'] = $fragment_size;
        foreach ($this->connections as $connection) {
            $connection->setOptions($this->options);
        }
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


    /* ---------- Connection broadcast operations ------------------------------------ */

    /**
     * Broadcast text message to all conenctions.
     * @param string $payload Content as string.
     */
    public function text(string $payload): void
    {
        $this->send($payload);
    }

    /**
     * Broadcast binary message to all conenctions.
     * @param string $payload Content as binary string.
     */
    public function binary(string $payload): void
    {
        $this->send($payload, 'binary');
    }

    /**
     * Broadcast ping message to all conenctions.
     * @param string $payload Optional text as string.
     */
    public function ping(string $payload = ''): void
    {
        $this->send($payload, 'ping');
    }

    /**
     * Broadcast pong message to all conenctions.
     * @param string $payload Optional text as string.
     */
    public function pong(string $payload = ''): void
    {
        $this->send($payload, 'pong');
    }

    /**
     * Send message on all connections.
     * @param string $payload Message to send.
     * @param string $opcode Opcode to use, default: 'text'.
     * @param bool $masked If message should be masked default: false.
     */
    public function send(string $payload, string $opcode = 'text', ?bool $masked = null): void
    {
        $masked = is_null($masked) ? false : $masked;
        if (!$this->isConnected()) {
            $this->connect();
        }
        if (!in_array($opcode, array_keys(self::$opcodes))) {
            $warning = "Bad opcode '{$opcode}'.  Try 'text' or 'binary'.";
            $this->logger->warning($warning);
            throw new BadOpcodeException($warning);
        }

        $factory = new Factory();
        $message = $factory->create($opcode, $payload);

        foreach ($this->connections as $connection) {
            $connection->pushMessage($message, $masked);
        }
    }

    /**
     * Close all connections.
     * @param int $status Close status, default: 1000.
     * @param string $message Close message, default: 'ttfn'.
     */
    public function close(int $status = 1000, string $message = 'ttfn'): void
    {
        foreach ($this->connections as $connection) {
            if ($connection->isConnected()) {
                $connection->close($status, $message);
            }
        }
    }

    /**
     * Disconnect all connections.
     */
    public function disconnect(): void
    {
        foreach ($this->connections as $connection) {
            if ($connection->isConnected()) {
                $connection->disconnect();
            }
        }
        $this->connections = [];
    }

    /**
     * Receive message from single connection.
     * Note that this operation will block reading and only read from first available connection.
     * @return mixed Message, text or null depending on settings.
     */
    public function receive()
    {
        $filter = $this->options['filter'];
        $return_obj = $this->options['return_obj'];

        if (!$this->isConnected()) {
            $this->connect();
        }
        $connection = current($this->connections);

        while (true) {
            $message = $connection->pullMessage();
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


    /* ---------- Connection functions ----------------------------------------------- */

    /**
     * Get requested path from last connection.
     * @return string Path.
     */
    public function getPath(): string
    {
        return $this->request->getUri()->getPath();
    }

    /**
     * Get request from last connection.
     * @return array Request.
     */
    public function getRequest(): array
    {
        return array_filter(explode("\r\n", $this->request->render()));
    }

    /**
     * Get headers from last connection.
     * @return string|null Headers.
     */
    public function getHeader($header): ?string
    {
        return $this->request->getHeaderLine($header) ?: null;
    }

    /**
     * Get last received opcode.
     * @return string|null Opcode.
     */
    public function getLastOpcode(): ?string
    {
        return $this->last_opcode;
    }

    /**
     * Get close status from single connection.
     * @return int|null Close status.
     */
    public function getCloseStatus(): ?int
    {
        return $this->connections ? current($this->connections)->getCloseStatus() : null;
    }

    /**
     * If Server has active connections.
     * @return bool True if active connection.
     */
    public function isConnected(): bool
    {
        foreach ($this->connections as $connection) {
            if ($connection->isConnected()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get name of local socket from single connection.
     * @return string|null Name of local socket.
     */
    public function getName(): ?string
    {
        return $this->isConnected() ? current($this->connections)->getName() : null;
    }

    /**
     * Get name of remote socket from single connection.
     * @return string|null Name of remote socket.
     */
    public function getRemoteName(): ?string
    {
        return $this->isConnected() ? current($this->connections)->getRemoteName() : null;
    }

    /**
     * @deprecated Will be removed in future version.
     */
    public function getPier(): ?string
    {
        trigger_error(
            'getPier() is deprecated and will be removed in future version. Use getRemoteName() instead.',
            E_USER_DEPRECATED
        );
        return $this->getRemoteName();
    }


    /* ---------- Helper functions --------------------------------------------------- */

    // Connect when read/write operation is performed.
    private function connect(): void
    {
        try {
            if (isset($this->options['timeout'])) {
                $socket = $this->listening->accept($this->options['timeout']);
            } else {
                $socket = $this->listening->accept();
            }
            if (!$socket) {
                throw new \RuntimeException('No socket');
            }
        } catch (\RuntimeException $e) {
            $error = "Server failed to connect. {$e->getMessage()}";
            $this->logger->error($error);
            throw new ConnectionException($error, ConnectionException::SERVER_ACCEPT_ERR, [], $e);
        }

        $connection = new Connection($socket, $this->options);
        $connection->setLogger($this->logger);

        if (isset($this->options['timeout'])) {
            $connection->setTimeout($this->options['timeout']);
        }

        $this->logger->info("Client has connected to port {port}", [
            'port' => $this->port,
        ]);
        $this->performHandshake($connection);
        $this->connections = ['*' => $connection];
    }

    // Perform upgrade handshake on new connections.
    private function performHandshake(Connection $connection): void
    {
        $request = new \WebSocket\Http\ServerRequest();
        $response = new \WebSocket\Http\Response(101);

        try {
            $request = $connection->pullHttp($request);
        } catch (\RuntimeException $e) {
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

        $this->request = $request;
    }
}
