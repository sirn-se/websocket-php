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
    SocketStream,
};
use Psr\Http\Message\{
    MessageInterface,
    RequestInterface,
    ResponseFactoryInterface,
    ResponseInterface,
    ServerRequestFactoryInterface,
    UriFactoryInterface,
};
use Psr\Log\{
    LoggerAwareInterface,
    LoggerInterface,
};
use Stringable;
use Throwable;
use WebSocket\Frame\FrameHandler;
use WebSocket\Http\HttpHandler;
use WebSocket\Exception\{
    ConnectionClosedException,
    ConnectionFailureException,
    ConnectionTimeoutException,
    ExceptionInterface,
    ReconnectException,
};
use WebSocket\Message\{
    Message,
    MessageHandler
};
use WebSocket\Middleware\{
    MiddlewareHandler,
    MiddlewareInterface
};
use WebSocket\Trait\{
    LoggerAwareTrait,
    SendMethodsTrait,
    StringableTrait
};

/**
 * WebSocket\Connection class.
 * A client/server connection, wrapping socket stream.
 */
class Connection implements LoggerAwareInterface, Stringable
{
    use LoggerAwareTrait;
    use SendMethodsTrait;
    use StringableTrait;

    private SocketStream $stream;
    private HttpHandler $httpHandler;
    private MessageHandler $messageHandler;
    private MiddlewareHandler $middlewareHandler;
    /** @var int<1, max> $frameSize */
    private int $frameSize = 4096;
    /** @var int<0, max>|float $timeout */
    private int|float $timeout = 60;
    private string $localName;
    private string $remoteName;
    private RequestInterface|null $handshakeRequest = null;
    private ResponseInterface|null $handshakeResponse = null;
    /** @var array<string, mixed> $meta */
    private array $meta = [];
    private bool $closed = false;


    /* ---------- Magic methods ------------------------------------------------------------------------------------ */

    public function __construct(
        SocketStream $stream,
        bool $pushMasked,
        bool $pullMaskedRequired,
        bool $ssl = false,
        HttpFactory|null $httpFactory = null
    ) {
        $this->stream = $stream;
        $this->httpHandler = new HttpHandler($this->stream, $ssl, $httpFactory);
        $this->messageHandler = new MessageHandler(new FrameHandler($this->stream, $pushMasked, $pullMaskedRequired));
        $this->middlewareHandler = new MiddlewareHandler($this->messageHandler, $this->httpHandler);
        $this->localName = $this->stream->getLocalName() ?? '<unknown>';
        $this->remoteName = $this->stream->getRemoteName() ?? '<unknown>';
        $this->initLogger();
    }

    public function __destruct()
    {
        if (!$this->closed && $this->isConnected()) {
            $this->stream->close();
        }
    }

    public function __toString(): string
    {
        return $this->stringable('%s:%s', $this->localName, $this->remoteName);
    }


    /* ---------- Configuration ------------------------------------------------------------------------------------ */

    /**
     * Set logger.
     * @param LoggerInterface $logger Logger implementation
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
        $this->messageHandler->setLogger($logger);
        $this->middlewareHandler->setLogger($logger);
        $this->logger->debug("[connection] Setting logger: " . get_class($logger));
    }

    /**
     * Set time out on connection.
     * @param int<0, max>|float $timeout Timeout part in seconds
     * @return self
     * @throws InvalidArgumentException
     */
    public function setTimeout(int|float $timeout): self
    {
        if ($timeout < 0) {
            throw new InvalidArgumentException("Invalid timeout '{$timeout}' provided");
        }
        $this->timeout = $timeout;
        $this->stream->setTimeout($timeout);
        $this->logger->debug("[connection] Setting timeout: {$timeout} seconds");
        return $this;
    }

    /**
     * Get timeout.
     * @return int<0, max>|float Timeout in seconds.
     */
    public function getTimeout(): int|float
    {
        return $this->timeout;
    }

    /**
     * Set frame size.
     * @param int<1, max> $frameSize Frame size in bytes.
     * @return self
     * @throws InvalidArgumentException
     */
    public function setFrameSize(int $frameSize): self
    {
        if ($frameSize < 1) {
            throw new InvalidArgumentException("Invalid frameSize '{$frameSize}' provided");
        }
        $this->frameSize = $frameSize;
        return $this;
    }

    /**
     * Get frame size.
     * @return int<1, max> Frame size in bytes
     */
    public function getFrameSize(): int
    {
        return max(1, $this->frameSize);
    }

    /**
     * Get current stream context.
     * @return Context
     */
    public function getContext(): Context
    {
        return $this->stream->getContext();
    }

    /**
     * Add a middleware.
     * @param MiddlewareInterface $middleware
     * @return self
     */
    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middlewareHandler->add($middleware);
        $this->logger->debug("[connection] Added middleware: {$middleware}");
        return $this;
    }


    /* ---------- Connection management ---------------------------------------------------------------------------- */

    /**
     * If connected to stream.
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->stream->isConnected();
    }

    /**
     * If connection is readable.
     * @return bool
     */
    public function isReadable(): bool
    {
        return $this->stream->isReadable();
    }

    /**
     * If connection is writable.
     * @return bool
     */
    public function isWritable(): bool
    {
        return $this->stream->isWritable();
    }

    /**
     * Close connection stream.
     * @return self
     */
    public function disconnect(): self
    {
        $this->logger->info('[connection] Closing connection');
        $this->stream->close();
        $this->closed = true;
        return $this;
    }

    /**
     * Close connection stream reading.
     * @return self
     */
    public function closeRead(): self
    {
        $this->logger->info('[connection] Closing further reading');
        $this->stream->closeRead();
        return $this;
    }

    /**
     * Close connection stream writing.
     * @return self
     */
    public function closeWrite(): self
    {
        $this->logger->info('[connection] Closing further writing');
        $this->stream->closeWrite();
        return $this;
    }


    /* ---------- Connection state --------------------------------------------------------------------------------- */

    /**
     * Get name of local socket, or null if not connected.
     * @return string|null
     */
    public function getName(): string|null
    {
        return $this->localName;
    }

    /**
     * Get name of remote socket, or null if not connected.
     * @return string|null
     */
    public function getRemoteName(): string|null
    {
        return $this->remoteName;
    }

    /**
     * Set meta value on connection.
     * @param string $key Meta key
     * @param mixed $value Meta value
     */
    public function setMeta(string $key, mixed $value): void
    {
        $this->meta[$key] = $value;
    }

    /**
     * Get meta value on connection.
     * @param string $key Meta key
     * @return mixed Meta value
     */
    public function getMeta(string $key): mixed
    {
        return $this->meta[$key] ?? null;
    }

    /**
     * Tick operation on connection.
     */
    public function tick(): void
    {
        $this->middlewareHandler->processTick($this);
    }


    /* ---------- WebSocket Message methods ------------------------------------------------------------------------ */

    /**
     * Send message.
     * @template T of Message
     * @param T $message
     * @return T
     */
    public function send(Message $message): Message
    {
        return $this->pushMessage($message);
    }

    /**
     * Push a message to stream.
     * @template T of Message
     * @param T $message
     * @return T
     */
    public function pushMessage(Message $message): Message
    {
        try {
            /** @throws Throwable */
            return $this->middlewareHandler->processOutgoing($this, $message);
        } catch (Throwable $e) {
            $this->throwException($e);
        }
    }

    /**
     * Pull a message from stream
     * @throws ExceptionInterface
     */
    public function pullMessage(): Message
    {
        try {
            /** @throws Throwable */
            return $this->middlewareHandler->processIncoming($this);
        } catch (Throwable $e) {
            $this->throwException($e);
        }
    }


    /* ---------- HTTP Message methods ----------------------------------------------------------------------------- */

    public function pushHttp(MessageInterface $message): MessageInterface
    {
        try {
            /** @throws Throwable */
            return $this->middlewareHandler->processHttpOutgoing($this, $message);
        } catch (Throwable $e) {
            $this->throwException($e);
        }
    }

    public function pullHttp(): MessageInterface
    {
        try {
            /** @throws Throwable */
            return $this->middlewareHandler->processHttpIncoming($this);
        } catch (Throwable $e) {
            $this->throwException($e);
        }
    }

    public function setHandshakeRequest(RequestInterface $request): self
    {
        $this->handshakeRequest = $request;
        return $this;
    }

    public function getHandshakeRequest(): RequestInterface|null
    {
        return $this->handshakeRequest;
    }

    public function setHandshakeResponse(ResponseInterface $response): self
    {
        $this->handshakeResponse = $response;
        return $this;
    }

    public function getHandshakeResponse(): ResponseInterface|null
    {
        return $this->handshakeResponse;
    }


    /* ---------- Internal helper methods -------------------------------------------------------------------------- */

    /**
     * @throws ReconnectException
     * @throws ExceptionInterface
     * @throws ConnectionTimeoutException
     * @throws ConnectionClosedException
     * @throws ConnectionFailureException
     */
    protected function throwException(Throwable $e): never
    {
        // Internal exceptions are handled and re-thrown
        if ($e instanceof ReconnectException) {
            $this->logger->info("[connection] {$e->getMessage()}", ['exception' => $e]);
            throw $e;
        }
        if ($e instanceof ExceptionInterface) {
            $this->logger->error("[connection] {$e->getMessage()}", ['exception' => $e]);
            throw $e;
        }
        // External exceptions are converted to internal
        if ($this->isConnected()) {
            $meta = $this->stream->getMetadata();
            $json = json_encode($meta);
            if (!empty($meta['timed_out'])) {
                $this->logger->error("[connection] {$e->getMessage()}", ['exception' => $e, 'meta' => $meta]);
                throw new ConnectionTimeoutException();
            }
            if (!empty($meta['eof'])) {
                $this->logger->error("[connection] {$e->getMessage()}", ['exception' => $e, 'meta' => $meta]);
                throw new ConnectionClosedException();
            }
        }
        $this->logger->error("[connection] {$e->getMessage()}", ['exception' => $e]);
        throw new ConnectionFailureException();
    }
}
