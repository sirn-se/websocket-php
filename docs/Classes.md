[Client](Client.md) • [Server](Server.md) • [Message](Message.md) • Classes • [Examples](Examples.md) • [Changelog](Changelog.md) • [Contributing](Contributing.md)

# Websocket: Classes

API for involved classes.

## Client

### `WebSocket\Client`

Main class for WebSocket client.

```php
class WebSocket\Client implements Psr\Log\LoggerAwareInterface
{
    // Magic methods

    public function __construct(Psr\Http\Message\UriInterface|string $uri, array $options = []);
    public function __toString(): string;

    // Configuration

    public function setStreamFactory(Phrity\Net\StreamFactory $streamFactory): void;
    public function setTimeout(int $timeout): void;
    public function setFragmentSize(int $fragment_size): self;
    public function getFragmentSize(): int;
    public function setLogger(Psr\Log\LoggerInterface $logger): void;

    // Messaging operations

    public function text(string $payload): void;
    public function binary(string $payload): void;
    public function ping(string $payload = ''): void;
    public function pong(string $payload = ''): void;
    public function close(int $status = 1000, string $message = 'ttfn'): void;
    public function send(WebSocket\Message\Message|string, string $opcode = 'text', bool|null $masked = null): void;
    public function receive(): WebSocket\Message\Message|null;

    // Connection management

    public function isConnected(): bool;
    public function connect(): void;
    public function disconnect(): void;

    // Connection state

    public function getCloseStatus(): int|null;
    public function getName(): string|null;
    public function getRemoteName(): string|null;
    public function getHandshakeResponse(): WebSocket\Http\Response|null;
}

```

## Server

### `WebSocket\Server`

Main class for WebSocket server.

```php
class WebSocket\Server implements Psr\Log\LoggerAwareInterface
{
    // Magic methods

    public function __construct(array $options = []);
    public function __toString(): string;

    // Configuration

    public function setStreamFactory(Phrity\Net\StreamFactory $streamFactory): void;
    public function setTimeout(int $timeout): void;
    public function setFragmentSize(int $fragment_size): self;
    public function getFragmentSize(): int;
    public function setLogger(Psr\Log\LoggerInterface $logger): void;

    // Messaging operations

    public function text(string $payload): WebSocket\Message\Text;
    public function binary(string $payload): WebSocket\Message\Binary;
    public function ping(string $payload = ''): WebSocket\Message\Ping;
    public function pong(string $payload = ''): WebSocket\Message\Pong;
    public function close(int $status = 1000, string $message = 'ttfn'): WebSocket\Message\Close;
    public function close(int $status = 1000, string $message = 'ttfn'): void;
    public function send(WebSocket\Message\Message): WebSocket\Message\Message;

    // Connection management

    public function isConnected(): bool;
    public function connect(): void;
    public function disconnect(): void;
    public function accept(): bool;

    // Connection state

    public function getCloseStatus(): int|null;
    public function getName(): string|null;
    public function getRemoteName(): string|null;
    public function getPort(): int
    public function getHandshakeRequest(): WebSocket\Http\Request|null;
}
```

## Core internals

### `WebSocket\Connection`

A connection between client and server.

```php
class WebSocket\Connection implements Psr\Log\LoggerAwareInterface
{
    // Construct & Destruct

    public function __construct(Phrity\Net\SocketStream $stream, array $options = []);
    public function __destruct();

    // Configuration

    public function setStreamFactory(Phrity\Net\StreamFactory $streamFactory): void;
    public function setTimeout(int $seconds, int $microseconds = 0): void;
    public function setOptions(array $options = []): void;
    public function setLogger(Psr\Log\LoggerInterface $logger): void;

    // Messaging operations

    public function text(string $payload): WebSocket\Message\Text;
    public function binary(string $payload): WebSocket\Message\Binary;
    public function ping(string $payload = ''): WebSocket\Message\Ping;
    public function pong(string $payload = ''): WebSocket\Message\Pong;
    public function close(int $status = 1000, string $message = 'ttfn'): WebSocket\Message\Close;
    public function send(WebSocket\Message\Message): WebSocket\Message\Message;
    public function receive(): WebSocket\Message\Message;

    // Connection management

    public function isConnected(): bool;
    public function disconnect(): bool;
    public function close(int $status = 1000, string $message = 'ttfn'): void;

    // Connection state

    public function getCloseStatus(): int|null;
    public function getName(): string|null;
    public function getRemoteName(): string|null;

    // WebSocket Message methods

    public function pushMessage(WebSocket\Message\Message $message, bool|null $masked = null): void;
    public function pullMessage(): WebSocket\Message\Message;

    // HTTP Message methods

    public function pushHttp(HWebSocket\Http\Message $message): void;
    public function pullHttp(): HWebSocket\Http\Message;
}
```

## WebSocket Messages

### `WebSocket\Message\Message`

A message sent or received over WebSocket connection.

```php
abstract class WebSocket\Messages\Messages
{
    public function __construct(string $payload = '');
    public function getOpcode(): string;
    public function getLength(): int;
    public function getTimestamp(): \DateTime;
    public function getContent(): string;
    public function setContent(string $payload = ''): void;
    public function hasContent(): bool;
    public function __toString(): string;
    public function getFrames(int $framesize = 4096): array;
}
```

### `WebSocket\Message\Binary`

WebSocket message of "binary" type.

```php
class WebSocket\Messages\Binary extends WebSocket\Messages\Message
{
    // @see WebSocket\Message\Message
}
```

### `WebSocket\Message\Close`

WebSocket message of "close"" type.

```php
class WebSocket\Messages\Close extends WebSocket\Messages\Message
{
    // @see WebSocket\Message\Message
}
```

### `WebSocket\Message\Ping`

WebSocket message of "ping"" type.

```php
class WebSocket\Messages\Ping extends WebSocket\Messages\Message
{
    // @see WebSocket\Message\Message
}
```

### `WebSocket\Message\Pong`

WebSocket message of "pong"" type.

```php
class WebSocket\Messages\Pong extends WebSocket\Messages\Message
{
    // @see WebSocket\Message\Message
}
```

### `WebSocket\Message\Text`

WebSocket message of "text"" type.

```php
class WebSocket\Messages\Text extends WebSocket\Messages\Message
{
    // @see WebSocket\Message\Message
}
```

### `WebSocket\Message\Factory`

Factory for creating WebSocket messages.

```php
class WebSocket\Messages\Factory
{
    public function create(string $opcode, string $payload = ''): WebSocket\Message\Message;
}
```

### `WebSocket\Message\MessageHandler`

Handler for WebSocket messages.

```php
class WebSocket\Messages\MessageHandler implements Psr\Log\LoggerAwareInterface
{
    public function __construct(WebSocket\Frame\FrameHandler $frameHandler);
    public function setLogger(Psr\Log\LoggerInterface $logger): void;
    public function push(WebSocket\Message\Message $message, bool $masked, int $size = self::DEFAULT_SIZE): void;
    public function pull(): WebSocket\Message\Message;
}
```

## Frames

### `WebSocket\Frame\Frame`

A frame sent or received on stream. A WebSocket message can contain 1 to many frames.

```php
class WebSocket\Frame\Frame
{
    public function __construct(string $opcode, string $payload, bool $final);
    public function isFinal(): bool;
    public function isContinuation(): bool;
    public function getOpcode(): string;
    public function getPayload(): string;
    public function getPayloadLength(): int;
}
```

### `WebSocket\Frame\FrameHandler`

Handler that read and write frames to stream.

```php
class WebSocket\Frame\FrameHandler implements Psr\Log\LoggerAwareInterface
{
    public function __construct(Phrity\Net\SocketStream $stream);
    public function setLogger(Psr\Log\LoggerInterface $logger): void;
    public function pull(): WebSocket\Frame\Frame;
    public function push(WebSocket\Frame\Frame $frame, bool $masked): int;
}
```

## HTTP

The HTTP functions are only used during handshake, when client and server upgrade to WebSocket connection.

### `WebSocket\Http\Message`

A HTTP message sent or received during handshake procedure.

```php
abstract class WebSocket\Http\Message implements Psr\Http\Message\MessageInterface
{
    public function getProtocolVersion(): string;
    public function withProtocolVersion(string $version): self;
    public function getHeaders(): array;
    public function hasHeader($name): bool;
    public function getHeader(string $name): array;
    public function getHeaderLine(string $name): string;
    public function withHeader(string $name, string|array $value): self;
    public function withAddedHeader(string $name, string|array $value): self;
    public function withoutHeader(string $name): self;
    public function getAsArray(): array;
}
```

### `WebSocket\Http\Request`

A HTTP request sent during handshake procedure.

```php
class WebSocket\Http\Request extends WebSocket\Http\Message implements Psr\Http\Message\RequestInterface
{
    public function __construct(string $method = 'GET', Psr\Http\Message\UriInterface|string|null $uri = null);
    public function getRequestTarget(): string;
    public function withRequestTarget(mixed $requestTarget): self;
    public function getMethod(): string;
    public function withMethod(string $method): self;
    public function getUri(): Psr\Http\Message\UriInterface;
    public function withUri(Psr\Http\Message\UriInterface $uri, bool $preserveHost = false): self;
    public function getAsArray(): array;

    // @see WebSocket\Http\Message
}
```

### `WebSocket\Http\Request`

A HTTP request as received by server during handshake procedure.

```php
class WebSocket\Http\ServerRequest extends WebSocket\Http\Request implements Psr\Http\Message\ServerRequestInterface
{
    public function getQueryParams(): array;

    // @see WebSocket\Http\Request
}
```

### `WebSocket\Http\Response`

A HTTP response sent or received during handshake procedure.

```php
class WebSocket\Http\Response extends WebSocket\Http\Message implements Psr\Http\Message\ResponseInterface
{
    public function __construct(int $code = 200, string $reasonPhrase = '');
    public function getStatusCode(): int;
    public function withStatus(int $code, string $reasonPhrase = ''): self;
    public function getReasonPhrase(): string;
    public function getAsArray(): array;

    // @see WebSocket\Http\Message
}
```

### `WebSocket\Http\HttpHandler`

Handler that read and write HTTP messages on stream.

```php
class WebSocket\Http\HttpHandler implements Psr\Log\LoggerAwareInterface
{
    public function __construct(Phrity\Net\SocketStream $stream);
    public function setLogger(Psr\Log\LoggerInterface $logger): void;
    public function pull(): Psr\Http\Message\MessageInterface;
    public function push(Psr\Http\Message\MessageInterface $message): int;
}
```

## Exceptions

### `WebSocket\Exception`

Abstract base class for WebSocket exceptions.

```php
abstract class WebSocket\Exception extends Exception
{
    // @see https://www.php.net/manual/en/class.exception
}
```

### `WebSocket\BadOpcodeException`

Thrown when bad opcode is sent or received.

```php
class WebSocket\BadOpcodeException extends Exception
{
    public function __construct(string $message, int $code = self::BAD_OPCODE, Throwable $prev = null);

    // @see Exception
}
```

### `WebSocket\BadUriException`

Thrown when invalid URI is provided.

```php
class WebSocket\BadUriException extends Exception
{
    // @see Exception
}
```

## `WebSocket\ConnectionException`

Thrown when connection operation fails.

```php
class WebSocket\ConnectionException extends Exception
{
    public function __construct(string $message, int $code = 0, array $data = [], Throwable $prev = null);
    public function getData(): array;

    // @see Exception
}
```

### `WebSocket\TimeoutException`

Thrown when connection has timed out.

```php
class WebSocket\TimeoutException extends ConnectionException
{
    // @see ConnectionException
}
```