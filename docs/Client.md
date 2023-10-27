[Documentation](Index.md) > Client

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

If you want to subscribe to messages sent by server at any point, use the listener functions.

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

The Client takes one argument: uri as a class implementing UriInterface or as string.
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

// Get current settings
echo "timeout:      {$client->getTimeout()}s\n";
echo "frame size:   {$client->getFrameSize()}b\n";
echo "running:      {$client->isRunning()}\n";
```

## Middlewares

Middlewares provide additional functionality when sending or receiving messages.
This repo comes with two middlewares that provide standard operability according to WebSocket protocol.

* `CloseHandler` - Automatically acts on incoming and outgoing Close requests, as specified in WebSocket protocol
* `PingResponder` - Responds with Pong message when receiving a Ping message, as specified in WebSocket protocol

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

## Listeners

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
    ->start();
    ;
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
