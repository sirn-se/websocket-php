[Documentation](../../) > [Classes](Classes.md) > Client

# Client class

```php
/**
 * WebSocket\Client class.
 * Entry class for WebSocket client.
 */
class Client implements implements Psr\Log\LoggerAwareInterface, Stringable
{
    use WebSocket\Trait\ListenerTrait;
    use WebSocket\Trait\OpcodeTrait;
    use WebSocket\Trait\SendMethodsTrait;

    /* ---------- Magic methods ------------------------------------------------------------------------------------ */

    /**
     * @param Psr\Http\Message\UriInterface|string $uri A ws/wss-URI
     */
    public function __construct(Psr\Http\Message\UriInterface|string $uri);

    /**
     * Get string representation of instance.
     * @return string String representation
     */
    public function __toString(): string;


    /* ---------- Configuration ------------------------------------------------------------------------------------ */

    /**
     * Set stream factory to use.
     * @param Phrity\Net\StreamFactory $streamFactory
     * @return self
     */
    public function setStreamFactory(Phrity\Net\StreamFactory $streamFactory): self;

    /**
     * Set logger.
     * @param Psr\Log\LoggerInterface $logger Logger implementation
     * @return self
     */
    public function setLogger(Psr\Log\LoggerInterface $logger): self;

    /**
     * Set timeout.
     * @param int $timeout Timeout in seconds
     * @return self
     * @throws InvalidArgumentException If invalid timeout provided
     */
    public function setTimeout(int $timeout): self;

    /**
     * Get timeout.
     * @return int Timeout in seconds
     */
    public function getTimeout(): int;

    /**
     * Set frame size.
     * @param int $frameSize Frame size in bytes
     * @return self
     * @throws InvalidArgumentException If invalid frameSize provided
     */
    public function setFrameSize(int $frameSize): self;

    /**
     * Get frame size.
     * @return int Frame size in bytes
     */
    public function getFrameSize(): int;

    /**
     * Set connection persistence.
     * @param bool $persistent True for persistent connection
     * @return self
     */
    public function setPersistent(bool $persistent): self;

    /**
     * Set connection context.
     * @param array $context Context as array, see https://www.php.net/manual/en/context.php
     * @return self
     */
    public function setContext(array $context): self;

    /**
     * Add header for handshake.
     * @param string $name Header name
     * @param string $content Header content
     * @return self
     */
    public function addHeader(string $name, string $content): self;

    /**
     * Add a middleware.
     * @param WebSocket\Middleware\MiddlewareInterface $middleware
     * @return self
     */
     public function addMiddleware(WebSocket\Middleware\MiddlewareInterface $middleware): self;


    /* ---------- Messaging operations ----------------------------------------------------------------------------- */

    /**
     * Send message.
     * @param WebSocket\Message\Message $message Message to send.
     * @return WebSocket\Message\Message Sent message
     */
    public function send(WebSocket\Message\Message $message): Message;

    /**
     * Receive message.
     * Note that this operation will block reading.
     * @return WebSocket\Message\Message|null
     */
    public function receive(): WebSocket\Message\Message|null;


    /* ---------- Listener operations ------------------------------------------------------------------------------ */

    /**
     * Start server listener.
     * @throws Throwable On low level error
     */
    public function start(): void;

    /**
     * Stop server listener (resumable).
     */
    public function stop(): void;

    /**
     * If server is running (accepting connections and messages).
     * @return bool
     */
    public function isRunning(): bool;


    /* ---------- Connection management ---------------------------------------------------------------------------- */

    /**
     * If Client has active connection.
     * @return bool True if active connection.
     */
    public function isConnected(): bool;

    /**
     * If Client is readable.
     * @return bool
     */
    public function isReadable(): bool;

    /**
     * If Client is writable.
     * @return bool
     */
    public function isWritable(): bool;

    /**
     * Connect to server and perform upgrade.
     * @throws ClientException On failed connection
     */
    public function connect(): void;

    /**
     * Disconnect from server.
     */
    public function disconnect(): void;


    /* ---------- Connection wrapper methods ----------------------------------------------------------------------- */

    /**
     * Get name of local socket, or null if not connected.
     * @return string|null
     */
    public function getName(): string|null;

    /**
     * Get name of remote socket, or null if not connected.
     * @return string|null
     */
    public function getRemoteName(): string|null;

    /**
     * Get Response for handshake procedure.
     * @return WebSocket\Http\Response|null Handshake.
     */
    public function getHandshakeResponse(): WebSocket\Http\Response|null;


    /* ---------- Internal helper methods -------------------------------------------------------------------------- */

    protected function performHandshake(Phrity\Net\Uri $host_uri): WebSocket\Http\Response;
    protected function generateKey(): string;
    protected function parseUri(Psr\Http\Message\UriInterface|string $uri): Phrity\Net\Uri
}
```