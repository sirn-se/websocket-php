[Documentation](Index.md) / Class Synopsis

# Class Synopsis

Incomplete list of public API of core classes.

## WebSocket

### Client

```php
class WebSocket\Client implements Psr\Log\LoggerAwareInterface, Stringable
{
    // Magic methods
    public function __construct(Psr\Http\Message\UriInterface|string $uri);
    public function __toString(): string;

    // Configuration
    public function setStreamFactory(Phrity\Net\StreamFactory $streamFactory): self;
    public function setLogger(Psr\Log\LoggerInterface $logger): void;
    public function setTimeout(int|float $timeout): self;
    public function getTimeout(): int|float;
    public function setFrameSize(int $frameSize): self;
    public function getFrameSize(): int;
    public function setPersistent(bool $persistent): self;
    public function getContext(): Phrity\Net\Context;
    public function setContext(Phrity\Net\Context|array $context): self;
    public function addHeader(string $name, string $content): self;
    public function addMiddleware(WebSocket\Middleware\MiddlewareInterface $middleware): self;

    // Messaging operations
    public function send(WebSocket\Message\Message $message): WebSocket\Message\Message;
    public function receive(): WebSocket\Message\Message;
    public function text(string $message): WebSocket\Message\Text;
    public function binary(string $message): WebSocket\Message\Binary;
    public function ping(string $message = ''): WebSocket\Message\Ping;
    public function pong(string $message = ''): WebSocket\Message\Pong;
    public function close(int $status = 1000, string $message = 'ttfn'): WebSocket\Message\Close;

    // Listener operations
    public function start(int|float|null $timeout = null): void;
    public function stop(): void;
    public function isRunning(): bool;

    // Connection management
    public function isConnected(): bool;
    public function isReadable(): bool;
    public function isWritable(): bool;
    public function connect(): void;
    public function disconnect(): void;

    // Connection wrapper methods
    public function getName(): string|null;
    public function getRemoteName(): string|null;
    public function getHandshakeResponse(): WebSocket\Http\Response|null;

    // Listener methods
    public function onDisconnect(Closure $closure): self;
    public function onHandshake(Closure $closure): self;
    public function onText(Closure $closure): self;
    public function onBinary(Closure $closure): self;
    public function onPing(Closure $closure): self;
    public function onPong(Closure $closure): self;
    public function onClose(Closure $closure): self;
    public function onError(Closure $closure): self;
    public function onTick(Closure $closure): self;
}
```

### Server

```php
class WebSocket\Server implements Psr\Log\LoggerAwareInterface, Stringable
{
    // Magic methods
    public function __construct(int $port = 80, bool $ssl = false);
    public function __toString(): string;

    // Configuration
    public function setStreamFactory(Phrity\Net\StreamFactory $streamFactory): self;
    public function setLogger(Psr\Log\LoggerInterface $logger): void;
    public function setTimeout(int|float $timeout): self;
    public function getTimeout(): int|float;
    public function setFrameSize(int $frameSize): self;
    public function getFrameSize(): int;
    public function getPort(): int;
    public function getScheme(): string;
    public function isSsl(): bool;
    public function getContext(): Phrity\Net\Context;
    public function setContext(Phrity\Net\Context|array $context): self;
    public function getConnectionCount(): int;
    public function getConnections(): array;
    public function getReadableConnections(): array;
    public function getWritableConnections(): array;
    public function addMiddleware(WebSocket\Middleware\MiddlewareInterface $middleware): self;
    public function setMaxConnections(int|null $maxConnections): self;

    // Messaging operations
    public function send(WebSocket\Message\Message $message): WebSocket\Message\Message;
    public function text(string $message): WebSocket\Message\Text;
    public function binary(string $message): WebSocket\Message\Binary;
    public function ping(string $message = ''): WebSocket\Message\Ping;
    public function pong(string $message = ''): WebSocket\Message\Pong;
    public function close(int $status = 1000, string $message = 'ttfn'): WebSocket\Message\Close;

    // Listener operations
    public function start(int|float|null $timeout = null): void;
    public function stop(): void;
    public function isRunning(): bool;

    // Connection management
    public function shutdown(int $closeStatus = 1001): void;
    public function disconnect(): void;

    // Listener methods
    public function onDisconnect(Closure $closure): self;
    public function onHandshake(Closure $closure): self;
    public function onText(Closure $closure): self;
    public function onBinary(Closure $closure): self;
    public function onPing(Closure $closure): self;
    public function onPong(Closure $closure): self;
    public function onClose(Closure $closure): self;
    public function onError(Closure $closure): self;
    public function onTick(Closure $closure): self;
}
```

### Connection

```php
class WebSocket\Connection implements Psr\Log\LoggerAwareInterface, Stringable
{
    // Magic methods
    public function __construct(Phrity\Net\SocketStream $stream, bool $pushMasked, bool $pullMaskedRequired, bool $ssl = false);
    public function __destruct();
    public function __toString(): string;

    // Configuration
    public function setLogger(Psr\Log\LoggerInterface $logger): void;
    public function setTimeout(int|float $timeout): self;
    public function getTimeout(): int|float;
    public function setFrameSize(int $frameSize): self;
    public function getFrameSize(): int;
    public function getContext(): Phrity\Net\Context;
    public function addMiddleware(WebSocket\Middleware\MiddlewareInterface $middleware): self;

    // Connection management
    public function isConnected(): bool;
    public function isReadable(): bool;
    public function isWritable(): bool;
    public function disconnect(): self;
    public function closeRead(): self;
    public function closeWrite(): self;

    // Connection state
    public function getName(): string|null;
    public function getRemoteName(): string|null;
    public function setMeta(string $key, mixed $value): void;
    public function getMeta(string $key): mixed;
    public function tick(): void;

    // WebSocket Message methods
    public function send(WebSocket\Message\Message $message): WebSocket\Message\Message;
    public function pushMessage(WebSocket\Message\Message $message): WebSocket\Message\Message;
    public function pullMessage(): WebSocket\Message\Message;
    public function text(string $message): WebSocket\Message\Text;
    public function binary(string $message): WebSocket\Message\Binary;
    public function ping(string $message = ''): WebSocket\Message\Ping;
    public function pong(string $message = ''): WebSocket\Message\Pong;
    public function close(int $status = 1000, string $message = 'ttfn'): WebSocket\Message\Close;

    // HTTP Message methods
    public function pushHttp(WebSocket\Http\Message $message): WebSocket\Http\Message;
    public function pullHttp(): WebSocket\Http\Message;
    public function setHandshakeRequest(WebSocket\Http\Request $request): self;
    public function getHandshakeRequest(): WebSocket\Http\Request|null;
    public function setHandshakeResponse(WebSocket\Http\Response $response): self;
    public function getHandshakeResponse(): WebSocket\Http\Response|null;
}
```

## WebSocket / Exception

### BadOpcodeException

```php
class WebSocket\Exception\BadOpcodeException extends WebSocket\Exception\Exception implements WebSocket\Exception\MessageLevelInterface
{
    public function __construct(string $message = 'Bad Opcode');
}
```

### BadUriException

```php
class WebSocket\Exception\BadUriException extends WebSocket\Exception\Exception
{
     public function __construct(string $message = 'Bad URI');
}
```

### ClientException

```php
class WebSocket\Exception\ClientException extends WebSocket\Exception\Exception
{
}
```

### CloseException

```php
class WebSocket\Exception\CloseException extends WebSocket\Exception\Exception
{
    public function __construct(int|null $status = null, string $content = '');
    public function getCloseStatus(): int|null;
}
```

### ConnectionClosedException

```php
class WebSocket\Exception\ConnectionClosedException extends WebSocket\Exception\Exception implements WebSocket\Exception\ConnectionLevelInterface
{
    public function __construct();
}
```

### ConnectionFailureException

```php
class WebSocket\Exception\ConnectionFailureException extends WebSocket\Exception\Exception implements WebSocket\Exception\ConnectionLevelInterface
{
    public function __construct();
}
```

### ConnectionLevelInterface

```php
interface WebSocket\Exception\ConnectionLevelInterface extends WebSocket\Exception\ExceptionInterface
{
}
```

### ConnectionTimeoutException

```php
class WebSocket\Exception\ConnectionTimeoutException extends WebSocket\Exception\Exception implements WebSocket\Exception\MessageLevelInterface
{
    public function __construct();
}
```

### Exception

```php
abstract class WebSocket\Exception\Exception extends RuntimeException implements WebSocket\Exception\ExceptionInterface
{
}
```

### ExceptionInterface

```php
interface WebSocket\Exception\ExceptionInterface
{
}
```

### HandshakeException

```php
class WebSocket\Exception\HandshakeException extends WebSocket\Exception\Exception implements WebSocket\Exception\ConnectionLevelInterface
{
    public function __construct(string $message, WebSocket\Http\Response $response);
    public function getResponse(): WebSocket\Http\Response;
}
```

### MessageLevelInterface

```php
interface WebSocket\Exception\MessageLevelInterface extends WebSocket\Exception\ExceptionInterface
{
}
```

### ReconnectException

```php
class WebSocket\Exception\ReconnectException extends WebSocket\Exception\Exception
{
    public function __construct(Uri|null $uri = null);
}
```

### ServerException

```php
class WebSocket\Exception\ServerException extends WebSocket\Exception\Exception
{
}
```

## WebSocket / Frame

### FrameHandler

```php
class WebSocket\Frame\FrameHandler implements LoggerAwareInterface, Stringable
{
    public function __construct(Phrity\Net\SocketStream $stream, bool $pushMasked, bool $pullMaskedRequired);
    public function __toString(): string;
    public function pull(): WebSocket\Frame\Frame;
    public function push(WebSocket\Frame\Frame $frame): int;
}
```

### Frame

```php
class WebSocket\Frame\Frame implements Stringable
{
    public function __construct(string $opcode, string $payload, bool $final, bool $rsv1 = false, bool $rsv2 = false, bool $rsv3 = false);
    public function __toString(): string;
    public function isFinal(): bool;
    public function getRsv1(): bool;
    public function getRsv2(): bool;
    public function getRsv3(): bool;
    public function isContinuation(): bool;
    public function getOpcode(): string;
    public function getPayload(): string;
    public function getPayloadLength(): int;
}
```

## WebSocket / Http

### HttpHandler

```php
class WebSocket\Http\HttpHandler implements LoggerAwareInterface, Stringable
{
    public function __construct(Phrity\Net\SocketStream $stream, bool $ssl = false);
    public function __toString(): string;
    public function pull(): Psr\Http\Message\MessageInterface;
    public function push(Psr\Http\Message\MessageInterface $message): Psr\Http\Message\MessageInterface;
}
```

### Message

```php
abstract class WebSocket\Http\Message implements Psr\Http\Message\MessageInterface, Stringable
{
    public function __toString(): string;
    public function getProtocolVersion(): string;
    public function withProtocolVersion(string $version): self;
    public function getHeaders(): array;
    public function hasHeader(string $name): bool;
    public function getHeader(string $name): array;
    public function getHeaderLine(string $name): string;
    public function withHeader(string $name, mixed $value): self;
    public function withAddedHeader(string $name, mixed $value): self;
    public function withoutHeader(string $name): self;
    public function getAsArray(): array;
}
```

### Request

```php
class WebSocket\Http\Request extends WebSocket\Http\Message implements Psr\Http\Message\RequestInterface
{
    public function __construct(string $method = 'GET', Psr\Http\Message\UriInterface|string|null $uri = null);
    public function __toString(): string;
    public function getRequestTarget(): string;
    public function withRequestTarget(mixed $requestTarget): self;
    public function getMethod(): string;
    public function withMethod(string $method): self;
    public function getUri(): Psr\Http\Message\UriInterface;
    public function withUri(Psr\Http\Message\UriInterface $uri, bool $preserveHost = false): self;
    public function getAsArray(): array;
}
```

### Response

```php
class WebSocket\Http\Response extends WebSocket\Http\Message implements Psr\Http\Message\ResponseInterface
{
    public function __construct(int $code = 200, string $reasonPhrase = '');
    public function __toString(): string;
    public function getStatusCode(): int;
    public function withStatus(int $code, string $reasonPhrase = ''): self;
    public function getReasonPhrase(): string;
    public function getAsArray(): array;
}
```

### ServerRequest

```php
class WebSocket\Http\ServerRequest extends WebSocket\Http\Request implements Psr\Http\Message\ServerRequestInterface
{
    public function __toString(): string;
}
```

## WebSocket / Message

### Binary

```php
class WebSocket\Message\Binary extends WebSocket\Message\Message
{
}
```

### Close

```php
class WebSocket\Message\Close extends WebSocket\Message\Message
{
    public function __construct(int|null $status = null, string $content = '');
    public function getCloseStatus(): int|null;
    public function setCloseStatus(int|null $status): void;
}
```

### Message

```php
class WebSocket\Message\Message implements Stringable
{
    public function __construct(string $content = '');
    public function __toString(): string;
    public function getOpcode(): string;
    public function getLength(): int;
    public function getTimestamp(): DateTimeInterface;
    public function getContent(): string;
    public function setContent(string $content = ''): void;
    public function hasContent(): bool;
    public function getPayload(): string;
    public function setPayload(string $payload = ''): void;
    public function getFrames(int $frameSize = 4096): array;
}
```

### MessageHandler

```php
class WebSocket\Message\MessageHandler implements Psr\Log\LoggerAwareInterface, Stringable
{
    public function __construct(WebSocket\Frame\FrameHandler $frameHandler);
    public function __toString(): string;
    public function setLogger(Psr\Log\LoggerInterface $logger): void;
    public function push(WebSocket\Message\Message $message, int $size = self::DEFAULT_SIZE): WebSocket\Message\Message;
    public function pull(): WebSocket\Message\Message;
}
```

### Ping

```php
class WebSocket\Message\Ping extends WebSocket\Message\Message
{
}
```

### Pong

```php
class WebSocket\Message\Pong extends WebSocket\Message\Message
{
}
```

### Text

```php
class WebSocket\Message\Text extends WebSocket\Message\Message
{
}
```

