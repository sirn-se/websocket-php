[Documentation](Index.md) / Client

# Websocket: Client

The client can read and write on a WebSocket stream.


## Subscribe operation

If you want to subscribe to messages sent by server at any point, use the listener functions.

```php
$client = new WebSocket\Client("wss://echo.websocket.org/");
$client
    // Add standard middlewares
    ->addMiddleware(new WebSocket\Middleware\CloseHandler())
    ->addMiddleware(new WebSocket\Middleware\PingResponder())
    // Listen to incoming Text messages
    ->onText(function (WebSocket\Client $client, WebSocket\Connection $connection, WebSocket\Message\Message $message) {
        // Act on incoming message
        echo "Got message: {$message->getContent()} \n";
        // Possibly respond to server
        $client->text("I got your your message");
    })
    ->start();
```
Optionally, `start()` can take timeout argument as int or float.


## Basic operation

Set up a WebSocket client for request/response strategy.
Manually pulling messages using `receive()` method is not recommended.

```php
$client = new WebSocket\Client("wss://echo.websocket.org/");
$client
    // Add standard middlewares
    ->addMiddleware(new WebSocket\Middleware\CloseHandler())
    ->addMiddleware(new WebSocket\Middleware\PingResponder())
    ;

// Send a message
$client->text("Hello WebSocket.org!");

// Read response (this is blocking)
$message = $client->receive();
echo "Got message: {$message->getContent()} \n";

// Close connection
$client->close();
```


## Middlewares

Middlewares provide additional functionality when sending or receiving messages.
This repo comes with two middlewares that provide standard operability according to WebSocket protocol.

* `CloseHandler` - Automatically acts on incoming and outgoing Close requests, as specified in WebSocket protocol
* `PingResponder` - Responds with Pong message when receiving a Ping message, as specified in WebSocket protocol

If not added, you need to handle close operation and respond to ping requests in your own implementation.

```php
$client = new WebSocket\Client("wss://echo.websocket.org/");
$client
    // Add CloseHandler middleware
    ->addMiddleware(new WebSocket\Middleware\CloseHandler())
    // Add PingResponder middleware
    ->addMiddleware(new WebSocket\Middleware\PingResponder())
    ;
```

Read more on [Middlewares](Middleware.md).

## Listeners

The message listeners are used by specifying a callback function that will be called
whenever the server receives a method of the same type.
All message listeners receive Client, Connection and Message as arguments.

```php
$client = new WebSocket\Client("wss://echo.websocket.org/");
$client
    // Listen to incoming Text messages
    ->onText(function (WebSocket\Client $client, WebSocket\Connection $connection, WebSocket\Message\Text $message) {
        // Act on incoming message
    })
    // Listen to incoming Binary messages
    ->onBinary(function (WebSocket\Client $client, WebSocket\Connection $connection, WebSocket\Message\Binary $message) {
        // Act on incoming message
    })
    ->start();
    ;
$client->isRunning(); // => True if currently running
```

Read more on [Listeners](Listener.md).

## Messages

WebSocket messages comes as any of five types; Text, Binary, Ping, Pong and Close.
The type is defined as opcode in WebSocket standard, and each classname corresponds to current message opcode.

Text and Binary are the main content message. The others are used for internal communication and typically do not contain content.
All provide the same methods, except Close that have an additional method not present on other types of messages.

```php
echo "opcode:       {$message->getOpcode()}\n";
echo "length:       {$message->getLength()}\n";
echo "timestamp:    {$message->getTimestamp()}\n";
echo "content:      {$message->getContent()}\n";
echo "close status: {$close->getCloseStatus()}\n";
```

Read more on [Messages](Message.md).

## Sending messages

To send a message to a server, call the send() method with a Message instance.
Any of the five message types can be sent this way.

```php
$client->send(new WebSocket\Message\Text("Server sends a message"));
$client->send(new WebSocket\Message\Binary($binary));
$client->send(new WebSocket\Message\Ping("My ping"));
$client->send(new WebSocket\Message\Text("My pong"));
$client->send(new WebSocket\Message\Close(1000, "Closing now"));
```
There are also convenience methods available for all types.
```php
$client->text("Server sends a message");
$client->binary($binary);
$client->ping("My ping");
$client->pong("My pong");
$client->close(1000, "Closing now");
```

## Configuration

The Client has one required argument: [URI](http://tools.ietf.org/html/rfc3986) as a class implementing [UriInterface](https://www.php-fig.org/psr/psr-7/#35-psrhttpmessageuriinterface) or as string.
The client support `ws` (`tcp`) and `wss` (`ssl`) schemas, depending on SSL configuration.

```php
__construct(
    Psr\Http\Message\UriInterface|string $uri,
    WebSocket\Configuration|null $configuration = null,
    Phrity\Net\StreamFactory|null $streamFactory = null,
);
```

Other options are available using the Configuration class.

- Logger
- Context
- Timeout
- Frame size
- Persistency

Read more on [Configuration](Configuration.md).

### HTTP factories

By default the Client wraps a [PSR-7 HTTP message](https://www.php-fig.org/psr/psr-7/) implementation.
Other implementations can be used by setting [PSR-17 HTTP factories](https://www.php-fig.org/psr/psr-17/) on the Client.

Set a configured HttpFactory class on the Client.
```php
$factory = new Phrity\Http\HttpFactory(
    requestFactory: $myRequestFactory,
    responseFactory: $myResponseFactory,
    uriFactory: $myUriFactory,
);
$client->setHttpFactory($factory);

```
Or if you use factories that support multiple interfaces, available in libraries such as `nyholm/psr7` or `guzzlehttp/psr7`.
```php
$psrFactory = new Nyholm\Psr7\Factory\Psr17Factory(); // Or any other PSR-17 factory
$client->setHttpFactory(Phrity\Http\HttpFactory::create($psrFactory));
```

### Handshake headers

Extra HTTP headers can be added, and used during handshake.

```php
$client->addHeader("Sec-WebSocket-Protocol", "soap");
```

## Connection control

Client will automatically connect when sending a message or starting the listener.
You may also connect and disconnect manually.

```php
if (!$client->isConnected()) {
    $client->connect();
}
$client->disconnect();
```

When connected, there are additional info to retrieve.

```php
// View client name
echo "local:    {$client->getName()}\n";

// View server name
echo "remote:   {$client->getRemoteName()}\n";

// Get meta data by key
echo "meta:   {$client->getMeta('some-metadata')}\n";

// Get response on handshake
$response = $client->getHandshakeResponse();
```

Read more on [Connection](Connection.md).

