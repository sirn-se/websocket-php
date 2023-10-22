Client • [Server](Server.md) • [Message](Message.md) • [Classes](Classes.md) • [Examples](Examples.md) • [Changelog](Changelog.md) • [Contributing](Contributing.md)

# Websocket: Client

The client can read and write on a WebSocket stream.

## Basic operation

Set up a WebSocket client for request/response strategy.

```php
$client = new WebSocket\Client("ws://echo.websocket.org/");
$client
    // Add standard middlewares
    ->addMiddleware(new WebSocket\Middleware\CloseHandler())
    ->addMiddleware(new WebSocket\Middleware\PingResponder())

// Send a message
$client->text("Hello WebSocket.org!");

// Read response (this is blocking)
echo $client->receive();

// Close connection
$client->close();
```

## Subscribe operation

If you need to subscribe to messages sent by server at any point, use the listener functions.

```php
$client = new WebSocket\Client("ws://echo.websocket.org/");
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

## Configuration

The Client takes one argument: url as a class implementing UriInterface or as string.
The client support `ws` (`tcp`) and `wss` (`ssl`) schemas, depending on SSL configuration.
Other options are available runtime by calling configuration methods.

```php
// Create client
$client = new WebSocket\Client("ws://echo.websocket.org/");
$client
    // Use a PSR-3 compatible logger
    ->setLogger(Psr\Log\LoggerInterface $logger)
    // Specify timeout in seconds (default 60 seconds)
    ->setTimeout(300)
    // Specify frame size in bytes (default 4096 bytes)
    ->setFrameSize(1024)
    // If client should attempt persistent connection
    ->setPersistent(true)
    // Set context
    ->setContext([
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false,
        ],
    ])
    // Add header to handshake request
    ->addHeader("Sec-WebSocket-Protocol", "soap")
    ;

echo "timeout:      {$client->getTimeout()}s\n";
echo "frame size:   {$client->getFrameSize()}b\n";
echo "running:      {$client->isRunning()}\n";
```

## Middlewares

Middlewares provide additional functionality when sending or receiving messages.
This repo comes with two middlewares that provide standard operability according to WebSocket protocol.

* CloseHandler - Automatically acts on incoming and outgoing Close requests, as specified in WebSocket protocol
* PingResponder - Responds with Pong message when receiving a Ping message, as specified in WebSocket protocol

If not added, you need to handle close operation and respond to ping requests in your own implementation.

```php
$client = new WebSocket\Client("ws://echo.websocket.org/");
$client
    // Add CloseHandler middleware
    ->addMiddleware(new WebSocket\Middleware\CloseHandler())
    // Add PingResponder middleware
    ->addMiddleware(new WebSocket\Middleware\PingResponder())
    ;
```

Read more on [Middlewares](Middlewares.md).

## Message listeners

The message listeners are used by specifying a callback function that will be called
whenever the server receives a method of the same type.
All message listeners receive Client, Connection and Message as arguments.

```php
$client = new WebSocket\Client("ws://echo.websocket.org/");
$client
    // Listen to incoming Text messages
    ->onText(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Text $message) {
        // Act on incoming message
    })
    // Listen to incoming Binary messages
    ->onBinary(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Binary $message) {
        // Act on incoming message
    })
    // Listen to incoming Ping messages
    ->onPing(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Ping $message) {
        // Act on incoming message
    })
    // Listen to incoming Pong messages
    ->onPong(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Pong $message) {
        // Act on incoming message
    })
    // Listen to incoming Close messages
    ->onClose(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Close $message) {
        // Act on incoming message
    })
    ;
```

## Connect, Disconnect and Error listeners

Some additional listeners are available for more advanced features.

```php
$client = new WebSocket\Client("ws://echo.websocket.org/");
$client
    // Called when a client connects
    ->onConnect(function (WebSocket\Client $client, WebSocket\Connection $connection, Psr\Http\Message\ServerRequestInterface $request) {
        // Act on connect
    })
    // Called when a client is disconnects
    ->onDisconnect(function (WebSocket\Client $client, WebSocket\Connection $connection) {
        // Act on disconnect
    })
    // When resolvable error occurs, this listener will be called
    ->onError(function (WebSocket\Client $client, WebSocket\Connection|null $connection, Exception $exception) {
        // Act on exception
    })
    ;
```

## Coroutine - The Tick listener

Using above functions, your server will be able to receive incoming messages and take action accordingly.

But what if your server need to process other data, and send unsolicited message to connected clients?
This is where coroutine pattern enters the picture.
The server might not be able to do things in parallell,
but it can give you space to run additional code not necessarily triggered by an incoming message.

Depending on workload and timeout configuration, the Tick listener will be called every now and then.

```php
$client = new WebSocket\Client("ws://echo.websocket.org/");
$client
    // Regulary called, regardless of WebSocket connections
    ->onTick(function (WebSocket\Client $client) {
        // Do anything
    })
    ;
```

## Messages

WebSocket messages comes as any of five types; Text, Binary, Ping, Pong and Close.
The type is defined as opcode in WebSocket standard, and each classname corresponds to current message opcode.

Text and Binary are the main content message. The others are used for internal communication and typically do not contain content.
All provide the same methods, excpet Close that have an additional method not present on other types of messages.

```php
echo "opcode:       {$message->getOpcode()}\n";
echo "length:       {$message->getLength()}\n";
echo "timestamp:    {$message->getTimestamp()}\n";
echo "content:      {$message->getContent()}\n";
echo "close status: {$close->getCloseStatus()}\n";
```

Read more on [Messages](Message.md).

## Sending a message to connected server

To send a message to a server, call the send() method with a Message instance.
Any of the five message types can be sent this way.

```php
$client->send(new WebSocket\Message\Text("Server sends a message"));
$client->send(new WebSocket\Message\Binary($binary));
$client->send(new WebSocket\Message\Ping("My ping"));
$client->send(new WebSocket\Message\Text("My pong"));
$client->send(new WebSocket\Message\Close(1000, "Closing now"));
```
The are also convenience methods available for all types.
```php
$client->text("Server sends a message");
$client->binary($binary);
$client->ping("My ping");
$client->pong("My pong");
$client->close(1000, "Closing now");
```

## Connection control

Client will automatically connect when sending a message or starting the listner.
You may also connect and disconnect manually.

```php
if (!$client->isConnected()) {
    $client->connect();
}
$client->disconnect();
```

When connected, there are addintional info to retrieve.

```php
// View client name
echo "local:    {$client->getName()}\n";

// View server name
echo "remote:   {$client->getRemoteName()}\n";

// Get response on handshake
$response = $client->getHandshakeResponse();
```
