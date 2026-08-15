abstract class WebSocket\Exception\Exception extends RuntimeException implements WebSocket\Exception\ExceptionInterface
{
}

abstract class WebSocket\Message\Message implements Stringable
{
    use WebSocket\Trait\StringableTrait;

    public method __construct(string $content = "");
    public method getContent(): string;
    public method getFrames(int $frameSize = 4096, WebSocket\Message\OpcodeRegistry|null $opcodeRegistry = null): array;
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

class WebSocket\Client implements WebSocket\Runtime\IdentityInterface, Psr\Log\LoggerAwareInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\ListenerTrait;
    use WebSocket\Trait\SendMethodsTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(Psr\Http\Message\UriInterface|string $uri, WebSocket\Configuration|null $configuration = null, Phrity\Net\StreamFactory|null $streamFactory = null, Phrity\Http\HttpFactory|null $httpFactory = null, WebSocket\Runtime\Runner|null $runner = null);
    public method __toString(): string;
    public method addHeader(string $name, string $content): self;
    public method addMiddleware(WebSocket\Middleware\MiddlewareInterface $middleware): self;
    public method connect(): void;
    public method disconnect(): void;
    public method getContext(): Phrity\Net\Context;
    public method getFrameSize(): int;
    public method getHandshakeResponse(): Psr\Http\Message\ResponseInterface|null;
    public method getIdentity(): string;
    public method getMeta(string $key): mixed;
    public method getName(): string|null;
    public method getRemoteName(): string|null;
    public method getTimeout(): int|float;
    public method isConnected(): bool;
    public method isReadable(): bool;
    public method isRunning(): bool;
    public method isWritable(): bool;
    public method receive(): WebSocket\Message\Message;
    public method send(WebSocket\Message\Message $message): WebSocket\Message\Message;
    public method setContext(Phrity\Net\Context|array $context): self;
    public method setFrameSize(int $frameSize): self;
    public method setHttpFactory(Phrity\Http\HttpFactory $httpFactory): self;
    public method setLogger(Psr\Log\LoggerInterface $logger): void;
    public method setPersistent(bool $persistent): self;
    public method setStreamFactory(Phrity\Net\StreamFactory $streamFactory): self;
    public method setTimeout(int|float $timeout): self;
    public method start(int|float|null $timeout = null): void;
    public method stop(): void;
}

class WebSocket\Configuration implements Psr\Log\LoggerAwareInterface, Stringable
{
    use WebSocket\Trait\StringableTrait;

    public method __construct(Psr\Log\LoggerInterface|null $logger = null, Phrity\Net\Context|null $context = null, int|float|null $timeout = null, int|null $frameSize = null, bool|null $persistent = null, int|null $maxConnections = null, WebSocket\Message\OpcodeRegistry|null $opcodeRegistry = null);
    public method __toString(): string;
    public method getContext(): Phrity\Net\Context;
    public method getFrameSize(): int;
    public method getLogger(): Psr\Log\LoggerInterface;
    public method getMaxConnections(): int|null;
    public method getOpcodeRegistry(): WebSocket\Message\OpcodeRegistry;
    public method getTimeout(): int|float;
    public method isPersistent(): bool;
    public method setContext(Phrity\Net\Context $context): void;
    public method setFrameSize(int $frameSize): void;
    public method setLogger(Psr\Log\LoggerInterface $logger): void;
    public method setMaxConnections(int|null $maxConnections): void;
    public method setOpcodeRegistry(WebSocket\Message\OpcodeRegistry $opcodeRegistry): void;
    public method setPersistent(bool $persistent): void;
    public method setTimeout(int|float $timeout): void;
}

class WebSocket\Connection implements WebSocket\Runtime\IdentityInterface, Psr\Log\LoggerAwareInterface, Phrity\Net\StreamContainerInterface, Stringable
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
    public method getFrameSize(): int;
    public method getHandshakeRequest(): Psr\Http\Message\RequestInterface|null;
    public method getHandshakeResponse(): Psr\Http\Message\ResponseInterface|null;
    public method getIdentity(): string;
    public method getMeta(string $key): mixed;
    public method getName(): string|null;
    public method getRemoteName(): string|null;
    public method getStream(): Phrity\Net\SocketStream;
    public method getTimeout(): int|float;
    public method isConnected(): bool;
    public method isReadable(): bool;
    public method isWritable(): bool;
    public method pullHttp(): Psr\Http\Message\MessageInterface;
    public method pullMessage(): WebSocket\Message\Message;
    public method pushHttp(Psr\Http\Message\MessageInterface $message): Psr\Http\Message\MessageInterface;
    public method pushMessage(WebSocket\Message\Message $message): WebSocket\Message\Message;
    public method send(WebSocket\Message\Message $message): WebSocket\Message\Message;
    public method setFrameSize(int $frameSize): self;
    public method setHandshakeRequest(Psr\Http\Message\RequestInterface $request): self;
    public method setHandshakeResponse(Psr\Http\Message\ResponseInterface $response): self;
    public method setLogger(Psr\Log\LoggerInterface $logger): void;
    public method setMeta(string $key, mixed $value): void;
    public method setTimeout(int|float $timeout): self;
    public method tick(): void;
}

class WebSocket\Exception\BadOpcodeException extends WebSocket\Exception\Exception implements WebSocket\Exception\MessageLevelInterface
{
    public method __construct(string $message = "Bad Opcode");
}

class WebSocket\Exception\BadUriException extends WebSocket\Exception\Exception implements WebSocket\Exception\HandlerLevelInterface
{
    public method __construct(string $message = "Bad URI");
}

class WebSocket\Exception\ClientException extends WebSocket\Exception\Exception implements WebSocket\Exception\HandlerLevelInterface
{
}

class WebSocket\Exception\CloseException extends WebSocket\Exception\Exception implements WebSocket\Exception\ControlInterface
{
    public method __construct(int|null $status = null, string $content = "");
    public method getCloseStatus(): int;
}

class WebSocket\Exception\ConnectionClosedException extends WebSocket\Exception\Exception implements WebSocket\Exception\ConnectionLevelInterface
{
    public method __construct();
}

class WebSocket\Exception\ConnectionFailureException extends WebSocket\Exception\Exception implements WebSocket\Exception\ConnectionLevelInterface
{
    public method __construct(string|null $message = null);
}

class WebSocket\Exception\ConnectionTimeoutException extends WebSocket\Exception\Exception implements WebSocket\Exception\MessageLevelInterface
{
    public method __construct();
}

class WebSocket\Exception\HandshakeException extends WebSocket\Exception\Exception implements WebSocket\Exception\ConnectionLevelInterface
{
    public method __construct(string $message, Psr\Http\Message\ResponseInterface $response);
    public method getResponse(): Psr\Http\Message\ResponseInterface;
}

class WebSocket\Exception\ReconnectException extends WebSocket\Exception\Exception implements WebSocket\Exception\ControlInterface
{
    public method __construct(Phrity\Net\Uri|null $uri = null);
    public method getUri(): Phrity\Net\Uri|null;
}

class WebSocket\Exception\RunnerException extends WebSocket\Exception\Exception implements WebSocket\Exception\HandlerLevelInterface
{
}

class WebSocket\Exception\ServerException extends WebSocket\Exception\Exception implements WebSocket\Exception\HandlerLevelInterface
{
}

class WebSocket\Frame\Frame implements Stringable
{
    use WebSocket\Trait\StringableTrait;

    public method __construct(int $opcode, string $payload, bool $final, bool $rsv1 = false, bool $rsv2 = false, bool $rsv3 = false);
    public method __toString(): string;
    public method getOpcode(): int;
    public method getPayload(): string;
    public method getPayloadLength(): int;
    public method getRsv1(): bool;
    public method getRsv2(): bool;
    public method getRsv3(): bool;
    public method isContinuation(): bool;
    public method isFinal(): bool;
    public method setRsv1(bool $rsv1): void;
}

class WebSocket\Frame\FrameHandler implements Psr\Log\LoggerAwareInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(Phrity\Net\SocketStream $stream, bool $pushMasked, bool $pullMaskedRequired, WebSocket\Configuration|null $configuration = null);
    public method pull(): WebSocket\Frame\Frame;
    public method push(WebSocket\Frame\Frame $frame): int;
    public method setLogger(Psr\Log\LoggerInterface $logger): void;
}

class WebSocket\Http\DefaultHttpFactory extends Phrity\Http\HttpFactory
{
    public method __construct();
    public method createRequest(string $method, mixed $uri): Psr\Http\Message\RequestInterface;
    public method createResponse(int $code = 200, string $reasonPhrase = ""): Psr\Http\Message\ResponseInterface;
    public method createServerRequest(string $method, mixed $uri, array $serverParams = []): Psr\Http\Message\ServerRequestInterface;
    public method createUri(string $uri = ""): Psr\Http\Message\UriInterface;
}

class WebSocket\Http\HttpHandler implements Psr\Log\LoggerAwareInterface, Stringable
{
    use WebSocket\Trait\StringableTrait;

    public method __construct(Phrity\Net\SocketStream $stream, bool $ssl = false, Phrity\Http\HttpFactory|null $httpFactory = null);
    public method pull(): Psr\Http\Message\MessageInterface;
    public method push(Psr\Http\Message\MessageInterface $message): Psr\Http\Message\MessageInterface;
    public method setLogger(Psr\Log\LoggerInterface $logger): void;
}

class WebSocket\Http\Request extends Nyholm\Psr7\Request implements Psr\Http\Message\RequestInterface
{
    public method __construct(string $method = "GET", Psr\Http\Message\UriInterface|string $uri = "");
}

class WebSocket\Http\Response extends Nyholm\Psr7\Response implements Psr\Http\Message\ResponseInterface
{
    public method __construct(int $code = 200, string $reasonPhrase = "");
}

class WebSocket\Http\ServerRequest extends Nyholm\Psr7\ServerRequest implements Psr\Http\Message\ServerRequestInterface
{
    public method __construct(string $method = "GET", Psr\Http\Message\UriInterface|string $uri = "");
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

class WebSocket\Message\MessageHandler implements Psr\Log\LoggerAwareInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(WebSocket\Frame\FrameHandler $frameHandler, WebSocket\Configuration|null $configuration = null);
    public method pull(): WebSocket\Message\Message;
    public method push(WebSocket\Message\Message $message, int $size = WebSocket\Message\MessageHandler::DEFAULT_SIZE): WebSocket\Message\Message;
    public method setLogger(Psr\Log\LoggerInterface $logger): void;
}

class WebSocket\Message\OpcodeRegistry
{
    public method createMessage(int $opcode): WebSocket\Message\Message;
    public method getOpcode(string $classname): int;
    public method register(int $opcode, string $classname): void;
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

class WebSocket\Middleware\Callback implements Psr\Log\LoggerAwareInterface, WebSocket\Middleware\ProcessHttpIncomingInterface, WebSocket\Middleware\ProcessHttpOutgoingInterface, WebSocket\Middleware\ProcessIncomingInterface, WebSocket\Middleware\ProcessOutgoingInterface, WebSocket\Middleware\ProcessTickInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(Closure|null $incoming = null, Closure|null $outgoing = null, Closure|null $httpIncoming = null, Closure|null $httpOutgoing = null, Closure|null $tick = null);
    public method processHttpIncoming(WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection): Psr\Http\Message\MessageInterface;
    public method processHttpOutgoing(WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection, Psr\Http\Message\MessageInterface $message): Psr\Http\Message\MessageInterface;
    public method processIncoming(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection): WebSocket\Message\Message;
    public method processOutgoing(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection, WebSocket\Message\Message $message): WebSocket\Message\Message;
    public method processTick(WebSocket\Middleware\ProcessTickStack $stack, WebSocket\Connection $connection): void;
    public method setLogger(Psr\Log\LoggerInterface $logger): void;
}

class WebSocket\Middleware\CloseHandler implements Psr\Log\LoggerAwareInterface, WebSocket\Middleware\ProcessIncomingInterface, WebSocket\Middleware\ProcessOutgoingInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct();
    public method processIncoming(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection): WebSocket\Message\Message;
    public method processOutgoing(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection, WebSocket\Message\Message $message): WebSocket\Message\Message;
    public method setLogger(Psr\Log\LoggerInterface $logger): void;
}

class WebSocket\Middleware\CompressionExtension implements Psr\Log\LoggerAwareInterface, WebSocket\Middleware\ProcessHttpOutgoingInterface, WebSocket\Middleware\ProcessHttpIncomingInterface, WebSocket\Middleware\ProcessIncomingInterface, WebSocket\Middleware\ProcessOutgoingInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(WebSocket\Middleware\CompressionExtension\CompressorInterface $compressors);
    public method processHttpIncoming(WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection): Psr\Http\Message\MessageInterface;
    public method processHttpOutgoing(WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection, Psr\Http\Message\MessageInterface $message): Psr\Http\Message\MessageInterface;
    public method processIncoming(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection): WebSocket\Message\Message;
    public method processOutgoing(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection, WebSocket\Message\Message $message): WebSocket\Message\Message;
    public method setLogger(Psr\Log\LoggerInterface $logger): void;
}

class WebSocket\Middleware\CompressionExtension\DeflateCompressor implements WebSocket\Middleware\CompressionExtension\CompressorInterface, Stringable
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

class WebSocket\Middleware\FollowRedirect implements Psr\Log\LoggerAwareInterface, WebSocket\Middleware\ProcessHttpIncomingInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(int $limit = 10);
    public method processHttpIncoming(WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection): Psr\Http\Message\MessageInterface;
    public method setLogger(Psr\Log\LoggerInterface $logger): void;
}

class WebSocket\Middleware\MiddlewareHandler implements Psr\Log\LoggerAwareInterface, Stringable
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
    public method setLogger(Psr\Log\LoggerInterface $logger): void;
}

class WebSocket\Middleware\PingInterval implements Psr\Log\LoggerAwareInterface, WebSocket\Middleware\ProcessOutgoingInterface, WebSocket\Middleware\ProcessTickInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(int|float|null $interval = null);
    public method processOutgoing(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection, WebSocket\Message\Message $message): WebSocket\Message\Message;
    public method processTick(WebSocket\Middleware\ProcessTickStack $stack, WebSocket\Connection $connection): void;
    public method setLogger(Psr\Log\LoggerInterface $logger): void;
}

class WebSocket\Middleware\PingResponder implements Psr\Log\LoggerAwareInterface, WebSocket\Middleware\ProcessIncomingInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct();
    public method processIncoming(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection): WebSocket\Message\Message;
    public method setLogger(Psr\Log\LoggerInterface $logger): void;
}

class WebSocket\Middleware\ProcessHttpStack implements Stringable
{
    use WebSocket\Trait\StringableTrait;

    public method __construct(WebSocket\Connection $connection, WebSocket\Http\HttpHandler $httpHandler, array $processors);
    public method handleHttpIncoming(): Psr\Http\Message\MessageInterface;
    public method handleHttpOutgoing(Psr\Http\Message\MessageInterface $message): Psr\Http\Message\MessageInterface;
}

class WebSocket\Middleware\ProcessStack implements Stringable
{
    use WebSocket\Trait\StringableTrait;

    public method __construct(WebSocket\Connection $connection, WebSocket\Message\MessageHandler $messageHandler, array $processors);
    public method handleIncoming(): WebSocket\Message\Message;
    public method handleOutgoing(WebSocket\Message\Message $message): WebSocket\Message\Message;
}

class WebSocket\Middleware\ProcessTickStack implements Stringable
{
    use WebSocket\Trait\StringableTrait;

    public method __construct(WebSocket\Connection $connection, array $processors);
    public method handleTick(): void;
}

class WebSocket\Middleware\SubprotocolNegotiation implements Psr\Log\LoggerAwareInterface, WebSocket\Middleware\ProcessHttpOutgoingInterface, WebSocket\Middleware\ProcessHttpIncomingInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(array $subprotocols, bool $require = false);
    public method processHttpIncoming(WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection): Psr\Http\Message\MessageInterface;
    public method processHttpOutgoing(WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection, Psr\Http\Message\MessageInterface $message): Psr\Http\Message\MessageInterface;
    public method setLogger(Psr\Log\LoggerInterface $logger): void;
}

class WebSocket\Runtime\Connections implements Countable, IteratorAggregate
{
    public method __construct(bool $pushMasked, bool $pullMaskedRequired, Phrity\Http\HttpFactory $httpFactory, WebSocket\Configuration $configuration);
    public method attach(WebSocket\Connection $connection): string;
    public method count(): int;
    public method create(Phrity\Net\SocketStream $stream, bool $ssl): WebSocket\Connection;
    public method detach(string $identity): void;
    public method filter(Closure $callback): self;
    public method first(): WebSocket\Connection|null;
    public method get(string $identity): WebSocket\Connection|null;
    public method getIterator(): Generator;
    public method has(string $identity): bool;
    public method isEmpty(): bool;
    public method reset(): void;
    public method toArray(): array;
    public method walk(Closure $callback): void;
}

class WebSocket\Runtime\Runner
{
    public method __construct(Phrity\Net\StreamFactory $streamFactory, Phrity\Net\StreamCollection|null $streamCollection = null);
    public method attach(Phrity\Net\StreamContainerInterface $streamContainer, Closure $onSelect, string $identity): void;
    public method detach(string $identity): void;
    public method handle(int|float $timeout): void;
    public method select(int|float $timeout): Phrity\Net\StreamCollection;
}

class WebSocket\Server implements WebSocket\Runtime\IdentityInterface, Psr\Log\LoggerAwareInterface, Phrity\Net\StreamContainerInterface, Stringable
{
    use WebSocket\Trait\ConfigurationTrait;
    use WebSocket\Trait\ListenerTrait;
    use WebSocket\Trait\SendMethodsTrait;
    use WebSocket\Trait\StringableTrait;

    public method __construct(int $port = 80, bool $ssl = false, WebSocket\Configuration|null $configuration = null, Phrity\Net\StreamFactory|null $streamFactory = null, Phrity\Http\HttpFactory|null $httpFactory = null, WebSocket\Runtime\Runner|null $runner = null);
    public method __toString(): string;
    public method addMiddleware(WebSocket\Middleware\MiddlewareInterface $middleware): self;
    public method disconnect(): void;
    public method getConnectionCount(): int;
    public method getConnections(): array;
    public method getContext(): Phrity\Net\Context;
    public method getFrameSize(): int;
    public method getIdentity(): string;
    public method getPort(): int;
    public method getReadableConnections(): array;
    public method getScheme(): string;
    public method getStream(): Phrity\Net\SocketServer;
    public method getTimeout(): int|float;
    public method getWritableConnections(): array;
    public method isRunning(): bool;
    public method isSsl(): bool;
    public method send(WebSocket\Message\Message $message): WebSocket\Message\Message;
    public method setContext(Phrity\Net\Context|array $context): self;
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

inteface WebSocket\Exception\ConnectionLevelInterface implements WebSocket\Exception\ExceptionInterface
{
}

inteface WebSocket\Exception\ControlInterface implements WebSocket\Exception\ExceptionInterface
{
}

inteface WebSocket\Exception\ExceptionInterface implements Throwable
{
    public method getMessage(): string;
}

inteface WebSocket\Exception\HandlerLevelInterface implements WebSocket\Exception\ExceptionInterface
{
}

inteface WebSocket\Exception\MessageLevelInterface implements WebSocket\Exception\ExceptionInterface
{
}

inteface WebSocket\Middleware\CompressionExtension\CompressorInterface implements Stringable
{
    public method compress(WebSocket\Message\Binary|WebSocket\Message\Text $message, object $configuration): WebSocket\Message\Binary|WebSocket\Message\Text;
    public method decompress(WebSocket\Message\Binary|WebSocket\Message\Text $message, object $configuration): WebSocket\Message\Binary|WebSocket\Message\Text;
    public method getConfiguration(string $element, bool $isServer): object;
    public method getRequestHeaderValue(): string;
    public method getResponseHeaderValue(object $configuration): string;
    public method isEligable(object $configuration): bool;
}

inteface WebSocket\Middleware\MiddlewareInterface implements Stringable
{
}

inteface WebSocket\Middleware\ProcessHttpIncomingInterface implements WebSocket\Middleware\MiddlewareInterface
{
    public method processHttpIncoming(WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection): Psr\Http\Message\MessageInterface;
}

inteface WebSocket\Middleware\ProcessHttpOutgoingInterface implements WebSocket\Middleware\MiddlewareInterface
{
    public method processHttpOutgoing(WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection, Psr\Http\Message\MessageInterface $message): Psr\Http\Message\MessageInterface;
}

inteface WebSocket\Middleware\ProcessIncomingInterface implements WebSocket\Middleware\MiddlewareInterface
{
    public method processIncoming(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection): WebSocket\Message\Message;
}

inteface WebSocket\Middleware\ProcessOutgoingInterface implements WebSocket\Middleware\MiddlewareInterface
{
    public method processOutgoing(WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection, WebSocket\Message\Message $message): WebSocket\Message\Message;
}

inteface WebSocket\Middleware\ProcessTickInterface implements WebSocket\Middleware\MiddlewareInterface
{
    public method processTick(WebSocket\Middleware\ProcessTickStack $stack, WebSocket\Connection $connection): void;
}

inteface WebSocket\Runtime\IdentityInterface
{
    public method getIdentity(): string;
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
    public method onConnect(Closure $closure): self;
    public method onDisconnect(Closure $closure): self;
    public method onError(Closure $closure): self;
    public method onHandshake(Closure $closure): self;
    public method onMessage(Closure $closure): self;
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
