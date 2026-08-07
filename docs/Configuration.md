[Documentation](Index.md) / Configuration

# Websocket: Configuration

The Configuration class is used to configure Client, Server and various worker classes.

## Using Configuration instance

When creating a Configuration, all constructor arguments are optional.
```php
$configuration = new WebSocket\Configuration(
    Psr\Log\LoggerInterface $logger,
    Phrity\Net\Context $context,
    int|float $timeout,
    int $frameSize,
    bool $persistent,
    int $maxConnections,
);
```

Provide Configuration in constructor;
```php
$client = new WebSocket\Client(
    uri: $uri,
    configuration: $configuration,
);
$server = new WebSocket\Server(
    port: $port,
    ssl: $ssl,
    configuration: $configuration,
);
```

Get and set Configuration;
```php
$configuration = $client->getConfiguration();
$client->setConfiguration($configuration);

$configuration = $server->getConfiguration();
$server->setConfiguration($configuration);
```


## Configuration options

### Logger

```
type: Psr\Log\LoggerInterface
default: Psr\Log\NullLogger
```

Attach any [PSR-3 compatible](https://www.php-fig.org/psr/psr-3/) logger.

```php
// Configuration instance
$configuration = new WebSocket\Configuration(logger: $logger);
$configuration->setLogger($logger);
$logger = $configuration->getLogger();

// Convenience setters
$client->setLogger($logger);
$server->setLogger($logger);
```

### Context

```
type: Phrity\Net\Context
default: Phrity\Net\Context // Empty context
```

Client and server support adding [context options and parameters](https://www.php.net/manual/en/context.php)
using the [Phrity\Net\Context](https://github.com/sirn-se/phrity-net-stream?tab=readme-ov-file#context-class) class.

```php
// Create and configure Context
$context = new Phrity\Net\Context();
$context->setOptions([
    "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false,
    ],
]);

// Configuration instance
$configuration = new WebSocket\Configuration(context: $context);
$configuration->setContext($context);
$context = $configuration->getContext();

// Convenience getters/setters
$context = $client->getContext();
$client->setContext($context);
$context = $server->getContext();
$server->setContext($context);
```

### Timeout

```
type: int<0, max>|float<0, max>
default: 60
```

Timeout for various operations can be specified in seconds.
This affects how long Client and Server will wait for connection, read and write operations, and listener scope.
Default is `60` seconds. Minimum is `0` seconds. Accepts int or float value.
Avoid setting very low values as it will cause a read loop that uses lots of the
available processing power even when there's nothing to read.

```php
// Configuration instance
$configuration = new WebSocket\Configuration(timeout: $timeout);
$configuration->setTimeout($timeout);
$timeout = $configuration->getTimeout();

// Convenience getters/setters
$timeout = $client->getTimeout();
$client->setTimeout($timeout);
$timeout = $server->getTimeout();
$server->setTimeout($timeout);
```

### Frame size

```
type: int<1, max>
default: 4096
```

Defines the maximum frame payload size in bytes.
Default is `4096` bytes. Minimum is `1` byte.
Do not change unless you have a strong reason to do so.

```php
// Configuration instance
$configuration = new WebSocket\Configuration(frameSize: $frameSize);
$configuration->setFrameSize($frameSize);
$frameSize = $configuration->getFrameSize();

// Convenience getters/setters
$frameSize = $client->getFrameSize();
$client->setFrameSize($frameSize);
$frameSize = $server->getFrameSize();
$server->setFrameSize($frameSize);
```

### Opcode registry

```
type: WebSocket\Message\OpcodeRegistry
default: Phrity\Net\OpcodeRegistry // Default mapping
```

By default, opcodes are mapped to built-in classes (text, binary, close, ping, pong).
With the OpcodeRegistry, the library can be configured to replace or add additional opcode imlementations.

```php
// Configuration instance
$configuration = new WebSocket\Configuration(opcodeRegistry: $opcodeRegistry);
$configuration->setOpcodeRegistry($opcodeRegistry);
$opcodeRegistry = $configuration->getOpcodeRegistry();
```

The `register(int $opcode, string $classname)` method will replace or add mapping.
- `$opcode` must be integer in range 1-15
- `$classname` must be class-string for class that extend `WebSocket\Message\Message`

```php
$opcodeRegistry = $configuration->getOpcodeRegistry();
$opcodeRegistry->register(1, 'MyTextMessage'); // Will replace Text for opcode=1
$opcodeRegistry->register(3, 'MyCustomMessage'); // Will add support for opcode=3
```

### Persistent connection (Client only)

```
type: bool
default: false
```

If set to true, the underlying connection will be kept open if possible.
This means that if Client closes and is then restarted, it may use the same connection.
Do not change unless you have a strong reason to do so.

```php
// Configuration instance
$configuration = new WebSocket\Configuration(persistent: $persistent);
$configuration->setPersistent($persistent);
$persistent = $configuration->isPersistent();

// Convenience setter
$client->setPersistent($persistent);
```

### Max connections (Server only)

```
type: int<1, max>|null
default: null // Unlimited
```

Limit maximum number of connections served. Any additional connection attempts will fail.
By default Server support unlimited number of connections.

```php
// Configuration instance
$configuration = new WebSocket\Configuration(maxConnections: $maxConnections);
$configuration->setMaxConnections($maxConnections);
$maxConnections = $configuration->getMaxConnections();

// Convenience setter
$server->setMaxConnections($maxConnections);
```

## Other configurable classes

```php
// Connection class
$connection = new WebSocket\Connection(..., configuration: $configuration);
$configuration = $connection->getConfiguration();
$connection->setConfiguration($configuration);

// FrameHandler class
$frameHandler = new WebSocket\Frame\FrameHandler(..., configuration: $configuration);
$configuration = $frameHandler->getConfiguration();
$frameHandler->setConfiguration($configuration);

// MessageHandler class
$messageHandler = new WebSocket\Message\MessageHandler(..., configuration: $configuration);
$configuration = $messageHandler->getConfiguration();
$messageHandler->setConfiguration($configuration);
```

If you need to set configuration on internal classes, best way is to clone the original;

```php
$clonedConfiguration = clone $source->getConfiguration();
$clonedConfiguration->setLogger(...);
$clonedConfiguration->setTimeout(...);
$clonedConfiguration->setFrameSize(...);
$source->setConfiguration($clonedConfiguration);
```

