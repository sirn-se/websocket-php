<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket;

use InvalidArgumentException;
use Phrity\Http\HttpFactory;
use Phrity\Net\{
    Context,
    SocketStream,
    StreamContainerInterface,
<<<<<<< HEAD
    StreamInterface,
=======
>>>>>>> v4.0-main
};
use Psr\Http\Message\{
    MessageInterface,
    RequestInterface,
    ResponseInterface,
<<<<<<< HEAD
=======
    ServerRequestFactoryInterface,
    UriFactoryInterface,
>>>>>>> v4.0-main
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
<<<<<<< HEAD
use WebSocket\Runtime\{
    HandlerInterface,
    SelectableInterface,
    IdentityInterface,
};
=======
use WebSocket\Runtime\IdentityInterface;
>>>>>>> v4.0-main
use WebSocket\Trait\{
    ConfigurationTrait,
    SendMethodsTrait,
    StringableTrait
};

/**
 * WebSocket\Connection class.
 * A client/server connection, wrapping socket stream.
 */
<<<<<<< HEAD
class Connection implements IdentityInterface, SelectableInterface. Stringable
=======
class Connection implements IdentityInterface, StreamContainerInterface, Stringable
>>>>>>> v4.0-main
{
    use ConfigurationTrait;
    use SendMethodsTrait;
    use StringableTrait;

<<<<<<< HEAD
    private HandlerInterface $handler;
=======
    private const SCOPE = 'connection';

>>>>>>> v4.0-main
    private SocketStream $stream;
    private HttpHandler $httpHandler;
    private MessageHandler $messageHandler;
    private MiddlewareHandler $middlewareHandler;
    private string $localName;
    private string $remoteName;
    private RequestInterface|null $handshakeRequest = null;
    private ResponseInterface|null $handshakeResponse = null;
    /** @var array<string, mixed> $meta */
    private array $meta = [];
    /** @var non-empty-string $identity */
<<<<<<< HEAD
    private string $identity = 'client/<unconnected>';
=======
    private string $identity = '*/connection/<unconnected>';
>>>>>>> v4.0-main


    /* ---------- Magic methods ------------------------------------------------------------------------------------ */

    public function __construct(
        HandlerInterface $handler,
        SocketStream $stream,
        bool $pushMasked,
        bool $pullMaskedRequired,
        bool $ssl = false,
        HttpFactory|null $httpFactory = null,
        Configuration|null $configuration = null,
    ) {
        $this->handler = $handler;
        $this->stream = $stream;
        $this->initConfiguration($configuration);
        $this->httpHandler = new HttpHandler($this->stream, $ssl, $httpFactory);
        $this->messageHandler = new MessageHandler(
<<<<<<< HEAD
            new FrameHandler($this->stream, $pushMasked, $pullMaskedRequired, $this->configuration),
            $this->configuration
        );
        $this->middlewareHandler = new MiddlewareHandler(
            $this->messageHandler,
            $this->httpHandler,
            $this->configuration
        );
        $this->localName = $this->stream->getLocalName() ?? '<unknown>';
        $this->remoteName = $this->stream->getRemoteName() ?? '<unknown>';
        $this->stream->setTimeout($this->configuration->getTimeout());
        $this->identity = sprintf(
            '%s/connection/%s/%s',
            $this->handler->getIdentity(),
            $this->getIdentityPart($this->localName),
            $this->getIdentityPart($this->remoteName),
        );
=======
            new FrameHandler($this->stream, $pushMasked, $pullMaskedRequired),
            $this->configuration
        );
        $this->middlewareHandler = new MiddlewareHandler($this->messageHandler, $this->httpHandler);
        $this->localName = $this->stream->getLocalName() ?? '<unknown>';
        $this->remoteName = $this->stream->getRemoteName() ?? '<unknown>';
        $this->identity = sprintf(
            '*/connection/%s/%s',
            $this->getIdentityPart($this->localName),
            $this->getIdentityPart($this->remoteName),
        );
        $this->stream->setTimeout($this->configuration->getTimeout());
>>>>>>> v4.0-main
    }

    public function __toString(): string
    {
        return $this->stringable('%s:%s', $this->localName, $this->remoteName);
    }

    public function getHandler(): HandlerInterface
    {
        return $this->handler;
    }

    public function getIdentity(): string
    {
        return $this->identity;
    }


    /* ---------- Configuration ------------------------------------------------------------------------------------ */

<<<<<<< HEAD
=======
    public function getIdentity(): string
    {
        return $this->identity;
    }

>>>>>>> v4.0-main
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
<<<<<<< HEAD
        $this->configuration->getLogger()->debug('[{identity}] Added middleware: {middleware}', [
            'identity' => $this->identity,
            'middleware' => (string)$middleware,
=======
        $this->configuration->getLogger()->debug("[{scope}] Added middleware: {middleware}", [
            'scope' => self::SCOPE,
            'connection' => $this->identity,
            'middleware' => $middleware,
>>>>>>> v4.0-main
        ]);
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
<<<<<<< HEAD
        $this->configuration->getLogger()->info('[{identity}] Closing connection', [
            'identity' => $this->identity,
=======
        $this->configuration->getLogger()->info("[{scope}] Closing connection", [
            'scope' => self::SCOPE,
            'connection' => $this->identity,
>>>>>>> v4.0-main
        ]);
        $this->stream->close();
        return $this;
    }

    /**
     * Close connection stream reading.
     * @return self
     */
    public function closeRead(): self
    {
<<<<<<< HEAD
        $this->configuration->getLogger()->info('[{identity}] Closing further reading', [
            'identity' => $this->identity,
=======
        $this->configuration->getLogger()->info("[{scope}] Closing further reading", [
            'scope' => self::SCOPE,
            'connection' => $this->identity,
>>>>>>> v4.0-main
        ]);
        $this->stream->closeRead();
        return $this;
    }

    /**
     * Close connection stream writing.
     * @return self
     */
    public function closeWrite(): self
    {
<<<<<<< HEAD
        $this->configuration->getLogger()->info('[{identity}] Closing further writing', [
            'identity' => $this->identity,
=======
        $this->configuration->getLogger()->info("[{scope}] Closing further writing", [
            'scope' => self::SCOPE,
            'connection' => $this->identity,
>>>>>>> v4.0-main
        ]);
        $this->stream->closeWrite();
        return $this;
    }


    /* ---------- Connection state --------------------------------------------------------------------------------- */

    /**
     * Get name of local socket, or null if not connected.
     * @return string
     */
    public function getName(): string
    {
        return $this->localName;
    }

    /**
     * Get name of remote socket, or null if not connected.
     * @return string
     */
    public function getRemoteName(): string
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

<<<<<<< HEAD
    public function getStream(): StreamInterface
=======
    public function getStream(): SocketStream
>>>>>>> v4.0-main
    {
        return $this->stream;
    }

<<<<<<< HEAD
    public function onSelect(): void
    {
        $this->getHandler()->selectHandler($this);
    }
=======
>>>>>>> v4.0-main

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
<<<<<<< HEAD
            $this->configuration->getLogger()->info('[{identity}] {error}', [
                'identity' => $this->identity,
                'exception' => $e,
                'error' => $e->getMessage(),
=======
            $this->configuration->getLogger()->info("[{scope}] {message}", [
                'scope' => self::SCOPE,
                'connection' => $this->identity,
                'exception' => $e,
                'message' => $e->getMessage(),
>>>>>>> v4.0-main
            ]);
            throw $e;
        }
        if ($e instanceof ExceptionInterface) {
<<<<<<< HEAD
            $this->configuration->getLogger()->error('[{identity}] {error}', [
                'identity' => $this->identity,
                'exception' => $e,
                'error' => $e->getMessage(),
=======
            $this->configuration->getLogger()->error("[{scope}] {message}", [
                'scope' => self::SCOPE,
                'connection' => $this->identity,
                'exception' => $e,
                'message' => $e->getMessage(),
>>>>>>> v4.0-main
            ]);
            throw $e;
        }
        // External exceptions are converted to internal
        if ($this->isConnected()) {
            $meta = $this->stream->getMetadata();
            $json = json_encode($meta);
            if (!empty($meta['timed_out'])) {
<<<<<<< HEAD
                $this->configuration->getLogger()->error('[{identity}] {error}', [
                    'identity' => $this->identity,
                    'exception' => $e,
                    'error' => $e->getMessage(),
                    'meta' => $meta,
=======
                $this->configuration->getLogger()->error("[{scope}] {message}", [
                    'scope' => self::SCOPE,
                    'connection' => $this->identity,
                    'exception' => $e,
                    'message' => $e->getMessage(),
                    'meta' => $meta
>>>>>>> v4.0-main
                ]);
                throw new ConnectionTimeoutException();
            }
            if (!empty($meta['eof'])) {
<<<<<<< HEAD
                $this->configuration->getLogger()->error('[{identity}] {error}', [
                    'identity' => $this->identity,
                    'exception' => $e,
                    'error' => $e->getMessage(),
                    'meta' => $meta,
                ]);
                throw new ConnectionClosedException($this, null, $e);
            }
        }
        $this->configuration->getLogger()->error('[{identity}] {error}', [
            'identity' => $this->identity,
            'exception' => $e,
            'error' => $e->getMessage(),
        ]);
        throw new ConnectionFailureException($this, null, $e);
    }

    protected function getIdentityPart(string $source): string
    {
        preg_match('/([0-9]+)$/', $source, $result);
        return empty($result) ? $source : array_shift($result);
=======
                $this->configuration->getLogger()->error("[{scope}] {message}", [
                    'scope' => self::SCOPE,
                    'connection' => $this->identity,
                    'exception' => $e,
                    'message' => $e->getMessage(),
                    'meta' => $meta
                ]);
                throw new ConnectionClosedException();
            }
        }
        $this->configuration->getLogger()->error("[{scope}] {message}", [
            'scope' => self::SCOPE,
            'connection' => $this->identity,
            'exception' => $e,
            'message' => $e->getMessage(),
        ]);
        throw new ConnectionFailureException();
>>>>>>> v4.0-main
    }

    protected function getIdentityPart(string $source): string
    {
        preg_match('/([0-9]+)$/', $source, $result);
        return empty($result) ? $source : array_shift($result);
    }
}
