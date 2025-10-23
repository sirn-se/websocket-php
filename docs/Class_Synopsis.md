[Documentation](Index.md) / Class Synopsis

# Class Synopsis

Public API of library classes.

```php
abstract class WebSocket\Exception\Exception extends RuntimeException imlements WebSocket\Exception\ExceptionInterface
{
}

abstract class WebSocket\Http\Message imlements Psr\Http\Message\MessageInterface, Stringable
{
    use WebSocket\Trait\StringableTrait;

    public method getAsArray(): array;
    public method getBody(): Psr\Http\Message\StreamInterface;
    public method getHeader(string $name): array;
    public method getHeaderLine(string $name): string;
    public method getHeaders(): array;
    public method getProtocolVersion(): string;
    public method hasHeader(string $name): bool;
    public method withAddedHeader(string $name, mixed $value): self;
    public method withBody(Psr\Http\Message\StreamInterface $body): self;
    public method withHeader(string $name, mixed $value): self;
    public method withProtocolVersion(string $version): self;
    public method withoutHeader(string $name): self;
}

abstract class WebSocket\Message\Message imlements Stringable
{
    use WebSocket\Trait\StringableTrait;

    public method __construct(string $content = "");
    public method getContent(): string;
    public method getFrames(int $frameSize = 4096): array;
    public method getLength(): int;
    public method getOpcode(): string;
    public method getPayload(): string;
    public method getTimestamp(): DateTimeInterface;
    public method hasContent(): bool;
    public method isCompressed(): bool;
    public method setCompress(bool $compress): void;
    public method setContent(string $content = ""): void;
    public method setPayload(string $payload = ""): void;
}

class WebSocket\Client imlements Psr\Log\LoggerAwareInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\ListenerTrait;
    use WebSocket\Trait\SendMethodsTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(Psr\Http\Message\UriInterface|string $uri, Phrity\Net\StreamFactory|null $streamFactory = null, Phrity\Http\HttpFactory|null $httpFactory = null, WebSocket\Runtime\Watcher|null $watcher = null, WebSocket\Configuration|null $configuration = null);
    public method __toString(): string;
    public method addHeader(string $name, string $content): self;
    public method addMiddleware(WebSocket\Middleware\MiddlewareInterface $middleware): self;
    public method connect(): void;
    public method disconnect(): void;
    public method getContext(): Phrity\Net\Context;
    public method getFrameSize(): int;
    public method getHandshakeResponse(): Psr\Http\Message\ResponseInterface|null;
    public method getName(): string|null;
    public method getRemoteName(): string|null;
    public method getTimeout(): int|float;
    public method isConnected(): bool;
    public method isReadable(): bool;
    public method isRunning(): bool;
    public method isWritable(): bool;
    public method receive(): WebSocket\Message\Message;
    public method send(WebSocket\Message\Message $message): WebSocket\Message\Message;
    public method setContext(Phrity\Net\Context $context): self;
    public method setFrameSize(int $frameSize): self;
    public method setHttpFactory(Phrity\Http\HttpFactory $httpFactory): self;
    public method setLogger(Psr\Log\LoggerInterface $logger): void;
    public method setPersistent(bool $persistent): self;
    public method setStreamFactory(Phrity\Net\StreamFactory $streamFactory): self;
    public method setTimeout(int|float $timeout): self;
    public method start(int|float|null $timeout = null): void;
    public method stop(): void;
}

class WebSocket\Configuration imlements Psr\Log\LoggerAwareInterface, Stringable
{
    use WebSocket\Trait\StringableTrait;

    public method __construct(Psr\Log\LoggerInterface|null $logger = null, Phrity\Net\Context|null $context = null, int|float|null $timeout = null, int|null $frameSize = null, bool|null $persistent = null, int|null $maxConnections = null);
    public method __toString(): string;
    public method getContext(): Phrity\Net\Context;
    public method getFrameSize(): int;
    public method getLogger(): Psr\Log\LoggerInterface;
    public method getMaxConnections(): int|null;
    public method getTimeout(): int|float;
    public method isPersistent(): bool;
    public method setContext(Phrity\Net\Context $context): void;
    public method setFrameSize(int $frameSize): void;
    public method setLogger(Psr\Log\LoggerInterface $logger): void;
    public method setMaxConnections(int|null $maxConnections): void;
    public method setPersistent(bool $persistent): void;
    public method setTimeout(int|float $timeout): void;
}

class WebSocket\Connection imlements Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\SendMethodsTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(Phrity\Net\SocketStream $stream, bool $pushMasked, bool $pullMaskedRequired, bool $ssl = false, Phrity\Http\HttpFactory|null $httpFactory = null, WebSocket\Configuration|null $configuration = null);
    public method __toString(): string;
    public method addMiddleware(WebSocket\Middleware\MiddlewareInterface $middleware): self;
    public method closeRead(): self;
    public method closeWrite(): self;
    public method disconnect(): self;
    public method getContext(): Phrity\Net\Context;
    public method getHandshakeRequest(): Psr\Http\Message\RequestInterface|null;
    public method getHandshakeResponse(): Psr\Http\Message\ResponseInterface|null;
    public method getMeta(string $key): mixed;
    public method getName(): string;
    public method getRemoteName(): string;
    public method isConnected(): bool;
    public method isReadable(): bool;
    public method isWritable(): bool;
    public method pullHttp(): Psr\Http\Message\MessageInterface;
    public method pullMessage(): WebSocket\Message\Message;
    public method pushHttp(Psr\Http\Message\MessageInterface $message): Psr\Http\Message\MessageInterface;
    public method pushMessage(WebSocket\Message\Message $message): WebSocket\Message\Message;
    public method send(WebSocket\Message\Message $message): WebSocket\Message\Message;
    public method setHandshakeRequest(Psr\Http\Message\RequestInterface $request): self;
    public method setHandshakeResponse(Psr\Http\Message\ResponseInterface $response): self;
    public method setMeta(string $key, mixed $value): void;
    public method tick(): void;
}

class WebSocket\Exception\BadOpcodeException extends WebSocket\Exception\Exception imlements WebSocket\Exception\MessageLevelInterface
{
    public method __construct(string $message = "Bad Opcode");
}

class WebSocket\Exception\BadUriException extends WebSocket\Exception\Exception
{
    public method __construct(string $message = "Bad URI");
}

class WebSocket\Exception\ClientException extends WebSocket\Exception\Exception
{
}

class WebSocket\Exception\CloseException extends WebSocket\Exception\Exception
{
    public method __construct(int|null $status = null, string $content = "");
    public method getCloseStatus(): int;
}

class WebSocket\Exception\ConnectionClosedException extends WebSocket\Exception\Exception imlements WebSocket\Exception\ConnectionLevelInterface
{
    public method __construct();
}

class WebSocket\Exception\ConnectionFailureException extends WebSocket\Exception\Exception imlements WebSocket\Exception\ConnectionLevelInterface
{
    public method __construct(string|null $message = null);
}

class WebSocket\Exception\ConnectionTimeoutException extends WebSocket\Exception\Exception imlements WebSocket\Exception\MessageLevelInterface
{
    public method __construct();
}

class WebSocket\Exception\HandshakeException extends WebSocket\Exception\Exception imlements WebSocket\Exception\ConnectionLevelInterface
{
    public method __construct(string $message, Psr\Http\Message\ResponseInterface $response);
    public method getResponse(): Psr\Http\Message\ResponseInterface;
}

class WebSocket\Exception\ReconnectException extends WebSocket\Exception\Exception
{
    public method __construct(Phrity\Net\Uri|null $uri = null);
    public method getUri(): Phrity\Net\Uri|null;
}

class WebSocket\Exception\ServerException extends WebSocket\Exception\Exception
{
}

class WebSocket\Frame\Frame imlements Stringable
{
    use WebSocket\Trait\StringableTrait;

    public method __construct(string $opcode, string $payload, bool $final, bool $rsv1 = false, bool $rsv2 = false, bool $rsv3 = false);
    public method __toString(): string;
    public method getOpcode(): string;
    public method getPayload(): string;
    public method getPayloadLength(): int;
    public method getRsv1(): bool;
    public method getRsv2(): bool;
    public method getRsv3(): bool;
    public method isContinuation(): bool;
    public method isFinal(): bool;
    public method setRsv1(bool $rsv1): void;
}

class WebSocket\Frame\FrameHandler imlements Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\OpcodeTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(Phrity\Net\SocketStream $stream, bool $pushMasked, bool $pullMaskedRequired, WebSocket\Configuration|null $configuration = null);
    public method pull(): WebSocket\Frame\Frame;
    public method push(WebSocket\Frame\Frame $frame): int;
}

class WebSocket\Http\DefaultHttpFactory extends Phrity\Http\HttpFactory
{
    public method __construct();
    public method createRequest(string $method, mixed $uri): Psr\Http\Message\RequestInterface;
    public method createResponse(int $code = 200, string $reasonPhrase = ""): Psr\Http\Message\ResponseInterface;
    public method createServerRequest(string $method, mixed $uri, array $serverParams = []): Psr\Http\Message\ServerRequestInterface;
    public method createUri(string $uri = ""): Psr\Http\Message\UriInterface;
}

class WebSocket\Http\HttpHandler imlements Stringable
{
    use WebSocket\Trait\StringableTrait;

    public method __construct(Phrity\Net\SocketStream $stream, bool $ssl = false, Phrity\Http\HttpFactory|null $httpFactory = null);
    public method pull(): Psr\Http\Message\MessageInterface;
    public method push(Psr\Http\Message\MessageInterface $message): Psr\Http\Message\MessageInterface;
}

class WebSocket\Http\Request extends WebSocket\Http\Message imlements Psr\Http\Message\RequestInterface
{
    public method __construct(string $method = "GET", Psr\Http\Message\UriInterface|string $uri = "");
    public method __toString(): string;
    public method getAsArray(): array;
    public method getMethod(): string;
    public method getRequestTarget(): string;
    public method getUri(): Psr\Http\Message\UriInterface;
    public method withMethod(string $method): self;
    public method withRequestTarget(mixed $requestTarget): self;
    public method withUri(Psr\Http\Message\UriInterface $uri, bool $preserveHost = false): self;
}

class WebSocket\Http\Response extends WebSocket\Http\Message imlements Psr\Http\Message\ResponseInterface
{
    public method __construct(int $code = 200, string $reasonPhrase = "");
    public method __toString(): string;
    public method getAsArray(): array;
    public method getReasonPhrase(): string;
    public method getStatusCode(): int;
    public method withStatus(int $code, string $reasonPhrase = ""): self;
}

class WebSocket\Http\ServerRequest extends WebSocket\Http\Request imlements Psr\Http\Message\ServerRequestInterface
{
    public method __toString(): string;
    public method getAttribute(string $name, mixed $default = null);
    public method getAttributes(): array;
    public method getCookieParams(): array;
    public method getParsedBody();
    public method getQueryParams(): array;
    public method getServerParams(): array;
    public method getUploadedFiles(): array;
    public method withAttribute(string $name, mixed $value): self;
    public method withCookieParams(array $cookies): self;
    public method withParsedBody(mixed $data): self;
    public method withQueryParams(array $query): self;
    public method withUploadedFiles(array $uploadedFiles): self;
    public method withoutAttribute(string $name): self;
}

class WebSocket\Message\Binary extends WebSocket\Message\Message
{
    public method isCompressed(): bool;
    public method setCompress(bool $compress): void;
}

class WebSocket\Message\Close extends WebSocket\Message\Message
{
    public method __construct(int|null $status = null, string $content = "");
    public method getCloseStatus(): int|null;
    public method getPayload(): string;
    public method setCloseStatus(int|null $status): void;
    public method setPayload(string $payload = ""): void;
}

class WebSocket\Message\MessageHandler imlements Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(WebSocket\Frame\FrameHandler $frameHandler, WebSocket\Configuration|null $configuration = null);
    public method pull(): WebSocket\Message\Message;
    public method push(WebSocket\Message\Message $message, int $size = WebSocket\Message\MessageHandler::DEFAULT_SIZE): WebSocket\Message\Message;
}

class WebSocket\Message\Ping extends WebSocket\Message\Message
{
}

class WebSocket\Message\Pong extends WebSocket\Message\Message
{
}

class WebSocket\Message\Text extends WebSocket\Message\Message
{
    public method isCompressed(): bool;
    public method setCompress(bool $compress): void;
}

class WebSocket\Middleware\Callback imlements WebSocket\Middleware\ProcessHttpIncomingInterface, WebSocket\Middleware\ProcessHttpOutgoingInterface, WebSocket\Middleware\ProcessIncomingInterface, WebSocket\Middleware\ProcessOutgoingInterface, WebSocket\Middleware\ProcessTickInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(Closure|null $incoming = null, Closure|null $outgoing = null, Closure|null $httpIncoming = null, Closure|null $httpOutgoing = null, Closure|null $tick = null);
    public method processHttpIncoming(WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection): Psr\Http\Message\MessageInterface;
    public method processHttpOutgoing(WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection, Psr\Http\Message\MessageInterface $message): Psr\Http\Message\MessageInterface;
    public method processIncoming(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection): WebSocket\Message\Message;
    public method processOutgoing(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection, WebSocket\Message\Message $message): WebSocket\Message\Message;
    public method processTick(WebSocket\Middleware\ProcessTickStack $stack, WebSocket\Connection $connection): void;
}

class WebSocket\Middleware\CloseHandler imlements WebSocket\Middleware\ProcessIncomingInterface, WebSocket\Middleware\ProcessOutgoingInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct();
    public method processIncoming(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection): WebSocket\Message\Message;
    public method processOutgoing(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection, WebSocket\Message\Message $message): WebSocket\Message\Message;
}

class WebSocket\Middleware\CompressionExtension imlements WebSocket\Middleware\ProcessHttpOutgoingInterface, WebSocket\Middleware\ProcessHttpIncomingInterface, WebSocket\Middleware\ProcessIncomingInterface, WebSocket\Middleware\ProcessOutgoingInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(WebSocket\Middleware\CompressionExtension\CompressorInterface $compressors);
    public method processHttpIncoming(WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection): Psr\Http\Message\MessageInterface;
    public method processHttpOutgoing(WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection, Psr\Http\Message\MessageInterface $message): Psr\Http\Message\MessageInterface;
    public method processIncoming(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection): WebSocket\Message\Message;
    public method processOutgoing(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection, WebSocket\Message\Message $message): WebSocket\Message\Message;
}

class WebSocket\Middleware\CompressionExtension\DeflateCompressor imlements WebSocket\Middleware\CompressionExtension\CompressorInterface, Stringable
{
    use WebSocket\Trait\StringableTrait;

    public method __construct(bool $serverNoContextTakeover = false, bool $clientNoContextTakeover = false, int $serverMaxWindowBits = WebSocket\Middleware\CompressionExtension\DeflateCompressor::MAX_WINDOW_SIZE, int $clientMaxWindowBits = WebSocket\Middleware\CompressionExtension\DeflateCompressor::MAX_WINDOW_SIZE, string $extension = "zlib");
    public method compress(WebSocket\Message\Binary|WebSocket\Message\Text $message, object $configuration): WebSocket\Message\Binary|WebSocket\Message\Text;
    public method decompress(WebSocket\Message\Binary|WebSocket\Message\Text $message, object $configuration): WebSocket\Message\Binary|WebSocket\Message\Text;
    public method getConfiguration(string $element, bool $isServer): object;
    public method getRequestHeaderValue(): string;
    public method getResponseHeaderValue(object $configuration): string;
    public method isEligable(object $configuration): bool;
}

class WebSocket\Middleware\FollowRedirect imlements WebSocket\Middleware\ProcessHttpIncomingInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(int $limit = 10);
    public method processHttpIncoming(WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection): Psr\Http\Message\MessageInterface;
}

class WebSocket\Middleware\MiddlewareHandler imlements Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(WebSocket\Message\MessageHandler $messageHandler, WebSocket\Http\HttpHandler $httpHandler, WebSocket\Configuration|null $configuration = null);
    public method add(WebSocket\Middleware\MiddlewareInterface $middleware): self;
    public method processHttpIncoming(WebSocket\Connection $connection): Psr\Http\Message\MessageInterface;
    public method processHttpOutgoing(WebSocket\Connection $connection, Psr\Http\Message\MessageInterface $message): Psr\Http\Message\MessageInterface;
    public method processIncoming(WebSocket\Connection $connection): WebSocket\Message\Message;
    public method processOutgoing(WebSocket\Connection $connection, WebSocket\Message\Message $message): WebSocket\Message\Message;
    public method processTick(WebSocket\Connection $connection): void;
}

class WebSocket\Middleware\PingInterval imlements WebSocket\Middleware\ProcessOutgoingInterface, WebSocket\Middleware\ProcessTickInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(int|float|null $interval = null);
    public method processOutgoing(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection, WebSocket\Message\Message $message): WebSocket\Message\Message;
    public method processTick(WebSocket\Middleware\ProcessTickStack $stack, WebSocket\Connection $connection): void;
}

class WebSocket\Middleware\PingResponder imlements WebSocket\Middleware\ProcessIncomingInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct();
    public method processIncoming(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection): WebSocket\Message\Message;
}

class WebSocket\Middleware\ProcessHttpStack imlements Stringable
{
    use WebSocket\Trait\StringableTrait;

    public method __construct(WebSocket\Connection $connection, WebSocket\Http\HttpHandler $httpHandler, array $processors);
    public method handleHttpIncoming(): Psr\Http\Message\MessageInterface;
    public method handleHttpOutgoing(Psr\Http\Message\MessageInterface $message): Psr\Http\Message\MessageInterface;
}

class WebSocket\Middleware\ProcessStack imlements Stringable
{
    use WebSocket\Trait\StringableTrait;

    public method __construct(WebSocket\Connection $connection, WebSocket\Message\MessageHandler $messageHandler, array $processors);
    public method handleIncoming(): WebSocket\Message\Message;
    public method handleOutgoing(WebSocket\Message\Message $message): WebSocket\Message\Message;
}

class WebSocket\Middleware\ProcessTickStack imlements Stringable
{
    use WebSocket\Trait\StringableTrait;

    public method __construct(WebSocket\Connection $connection, array $processors);
    public method handleTick(): void;
}

class WebSocket\Middleware\SubprotocolNegotiation imlements WebSocket\Middleware\ProcessHttpOutgoingInterface, WebSocket\Middleware\ProcessHttpIncomingInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(array $subprotocols, bool $require = false);
    public method processHttpIncoming(WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection): Psr\Http\Message\MessageInterface;
    public method processHttpOutgoing(WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection, Psr\Http\Message\MessageInterface $message): Psr\Http\Message\MessageInterface;
}

class WebSocket\Runtime\Watcher
{
    public method __construct(Phrity\Net\StreamCollection $streamCollection);
    public method attach(string $key, Phrity\Net\StreamInterface $attach, Closure $onSelect): void;
    public method detach(string $key): void;
    public method isEmpty(): bool;
    public method watch(float $timeout): void;
}

class WebSocket\Server imlements Psr\Log\LoggerAwareInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\ListenerTrait;
    use WebSocket\Trait\SendMethodsTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(int $port = 80, bool $ssl = false, Phrity\Net\StreamFactory|null $streamFactory = null, Phrity\Http\HttpFactory|null $httpFactory = null, WebSocket\Runtime\Watcher|null $watcher = null, WebSocket\Configuration|null $configuration = null);
    public method __toString(): string;
    public method addMiddleware(WebSocket\Middleware\MiddlewareInterface $middleware): self;
    public method disconnect(): void;
    public method getConnectionCount(): int;
    public method getConnections(): array;
    public method getContext(): Phrity\Net\Context;
    public method getFrameSize(): int;
    public method getPort(): int;
    public method getReadableConnections(): array;
    public method getScheme(): string;
    public method getTimeout(): int|float;
    public method getWritableConnections(): array;
    public method isRunning(): bool;
    public method isSsl(): bool;
    public method send(WebSocket\Message\Message $message): WebSocket\Message\Message;
    public method setContext(Phrity\Net\Context $context): self;
    public method setFrameSize(int $frameSize): self;
    public method setHttpFactory(Phrity\Http\HttpFactory $httpFactory): self;
    public method setLogger(Psr\Log\LoggerInterface $logger): void;
    public method setMaxConnections(int|null $maxConnections): self;
    public method setStreamFactory(Phrity\Net\StreamFactory $streamFactory): self;
    public method setTimeout(int|float $timeout): self;
    public method shutdown(int $closeStatus = 1001): void;
    public method start(int|float|null $timeout = null): void;
    public method stop(): void;
}

inteface WebSocket\Constant
{
    public const GUID;
}

inteface WebSocket\Exception\ConnectionLevelInterface imlements WebSocket\Exception\ExceptionInterface
{
}

inteface WebSocket\Exception\ExceptionInterface imlements Throwable
{
    public method getMessage(): string;
}

inteface WebSocket\Exception\MessageLevelInterface imlements WebSocket\Exception\ExceptionInterface
{
}

inteface WebSocket\Middleware\CompressionExtension\CompressorInterface imlements Stringable
{
    public method compress(WebSocket\Message\Binary|WebSocket\Message\Text $message, object $configuration): WebSocket\Message\Binary|WebSocket\Message\Text;
    public method decompress(WebSocket\Message\Binary|WebSocket\Message\Text $message, object $configuration): WebSocket\Message\Binary|WebSocket\Message\Text;
    public method getConfiguration(string $element, bool $isServer): object;
    public method getRequestHeaderValue(): string;
    public method getResponseHeaderValue(object $configuration): string;
    public method isEligable(object $configuration): bool;
}

inteface WebSocket\Middleware\MiddlewareInterface imlements Stringable
{
    public method setConfiguration(WebSocket\Configuration $configuration): self;
}

inteface WebSocket\Middleware\ProcessHttpIncomingInterface imlements WebSocket\Middleware\MiddlewareInterface
{
    public method processHttpIncoming(WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection): Psr\Http\Message\MessageInterface;
}

inteface WebSocket\Middleware\ProcessHttpOutgoingInterface imlements WebSocket\Middleware\MiddlewareInterface
{
    public method processHttpOutgoing(WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection, Psr\Http\Message\MessageInterface $message): Psr\Http\Message\MessageInterface;
}

inteface WebSocket\Middleware\ProcessIncomingInterface imlements WebSocket\Middleware\MiddlewareInterface
{
    public method processIncoming(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection): WebSocket\Message\Message;
}

inteface WebSocket\Middleware\ProcessOutgoingInterface imlements WebSocket\Middleware\MiddlewareInterface
{
    public method processOutgoing(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection, WebSocket\Message\Message $message): WebSocket\Message\Message;
}

inteface WebSocket\Middleware\ProcessTickInterface imlements WebSocket\Middleware\MiddlewareInterface
{
    public method processTick(WebSocket\Middleware\ProcessTickStack $stack, WebSocket\Connection $connection): void;
}

trait WebSocket\Trait\ConfigurationTrait
{
    public method getConfiguration(): WebSocket\Configuration;
    public method initConfiguration(WebSocket\Configuration|null $configuration = null): self;
    public method setConfiguration(WebSocket\Configuration $configuration): self;
}

trait WebSocket\Trait\ListenerTrait
{
    public method onBinary(Closure $closure): self;
    public method onClose(Closure $closure): self;
    public method onDisconnect(Closure $closure): self;
    public method onError(Closure $closure): self;
    public method onHandshake(Closure $closure): self;
    public method onPing(Closure $closure): self;
    public method onPong(Closure $closure): self;
    public method onText(Closure $closure): self;
    public method onTick(Closure $closure): self;
}

trait WebSocket\Trait\OpcodeTrait
{
}

trait WebSocket\Trait\SendMethodsTrait
{
    public method binary(string $message): WebSocket\Message\Binary;
    public method close(int $status = 1000, string $message = "ttfn"): WebSocket\Message\Close;
    public method ping(string $message = ""): WebSocket\Message\Ping;
    public method pong(string $message = ""): WebSocket\Message\Pong;
    public method text(string $message): WebSocket\Message\Text;
}

trait WebSocket\Trait\StringableTrait
{
    public method __toString(): string;
}
```