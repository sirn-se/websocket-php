[Documentation](../../) > [Classes](Classes.md) > Server

# Server class

```php
class WebSocket\Server implements Psr\Log\LoggerAwareInterface, Stringable
{
    use WebSocket\Trait\ListenerTrait;
    use WebSocket\Trait\OpcodeTrait;
    use WebSocket\Trait\SendMethodsTrait;


    /* ---------- Magic methods ------------------------------------------------------------------------------------ */

    /**
     * @param int $port Socket port to listen to
     * @param string $scheme Scheme (tcp or ssl)
     * @throws InvalidArgumentException If invalid port provided
     */
    public function __construct(int $port = 80, bool $ssl = false);

    /**
     * Get string representation of instance.
     * @return string String representation.
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
     * Get socket port number.
     * @return int port
     */
    public function getPort(): int;

    /**
     * Get connection scheme.
     * @return string scheme
     */
    public function getScheme(): string;

    /**
     * Number of currently connected clients.
     * @return int Connection count
     */
    public function getConnectionCount(): int;

    /**
     * Add a middleware.
     * @param WebSocket\Middleware\MiddlewareInterface $middleware
     * @return self
     */
     public function addMiddleware(WebSocket\Middleware\MiddlewareInterface $middleware): self;


    /* ---------- Messaging operations ----------------------------------------------------------------------------- */

    /**
     * Send message (broadcast to all connected clients).
     * @param WebSocket\Message\Message $message Message to send
     */
    public function send(WebSocket\Message\Message $message): Message


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
     * Disconnect all connections and stop server.
     */
    public function disconnect(): void;


    /* ---------- Internal helper methods -------------------------------------------------------------------------- */

    protected function createSocketServer(): void;
    protected function acceptSocket(Phrity\Net\SocketServer $socket): void;
    protected function detachUnconnected(): void;
    protected function performHandshake(WebSocket\Connection $connection): ServerRequest;
}
```