[Documentation](Index.md) / Connection

# Websocket: Connection

A Connection represents the actual connection between client and server.
The Client only has a single Connection. The Server can have zero to many Connections.

The Connection instances are typically exposed in [Listeners](Listener.md) callbacks.


## Sending messages

To send a message on connection, call the send() method with a Message instance.
Any of the five message types can be sent this way.

```php
$connection->send(new WebSocket\Message\Text("Server sends a message"));
$connection->send(new WebSocket\Message\Binary($binary));
$connection->send(new WebSocket\Message\Ping("My ping"));
$connection->send(new WebSocket\Message\Text("My pong"));
$connection->send(new WebSocket\Message\Close(1000, "Closing now"));
```
There are also convenience methods available for all types.
```php
$connection->text("Server sends a message");
$connection->binary($binary);
$connection->ping("My ping");
$connection->pong("My pong");
$connection->close(1000, "Closing now");
```

## Connection management

```php
// Is connection open?
$connection->isConnected();

// Is connection readable?
$connection->isReadable();

// Is connection writable?
$connection->isWritable();

// Immediately disconnect client without normal close procedure
$connection->disconnect();

// Close connection for reading (may disconnect if already closed for writing)
$connection->closeRead();

// Close connection for writing (may disconnect if already closed for reading)
$connection->closeWrite();
```

## Connection state

```php
// Get local name for connection
$connection->getName();

// Get remote name for connection
$connection->getRemoteName();

// Get and set associated meta data on connection
$connection->setMeta('myMetaData', $anything);
$connection->getMeta('myMetaData');

// Trigger a tick event on connection
$connection->tick();
```

## Handshake result

```php
// Get the Request sent during handshake procedure
$connection->getHandshakeRequest();

// Get the Response sent during handshake procedure
$connection->getHandshakeResponse();
```

## Configuration

Some options are available runtime by calling configuration methods.

### Logger

Connection support adding any [PSR-4 compatible](https://www.php-fig.org/psr/psr-3/) logger.

```php
$connection->setLogger(Psr\Log\LoggerInterface $logger);
```

### Timeout

Timeout for various operations can be specified in seconds.
This affects how long a Connection will wait for read and write operations.
Default is `60` seconds. Minimum is `0` seconds.  Accepts int or float value.
Avoid setting very low values as it will cause a read loop to use all
available processing power even when there's nothing to read.

```php
$connection->setTimeout(300); // set timeout in seconds
$connection->getTimeout(); // => current timeout in seconds
```

### Frame size

Defines the maximum payload per frame size in bytes.
Default is `4096` bytes. Minimum is `1` byte.
Do not change unless you have a strong reason to do so.

```php
$connection->setFrameSize(1024); // set maximum payload frame size in bytes
$connection->getFrameSize(); // => current maximum payload frame size in bytes
```

### Context

Connection exposes [context options and parameters](https://www.php.net/manual/en/context.php)
using the [Phrity\Net\Context](https://github.com/sirn-se/phrity-net-stream?tab=readme-ov-file#context-class) class.

```php
$context = $connection->getContext();
$context->getOption("ssl", "verify_peer");
$context->setOption("ssl", "verify_peer", false);
```
