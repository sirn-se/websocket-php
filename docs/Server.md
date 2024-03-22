[Documentation](Index.md) / Server

# Websocket: Server

The library contains a multi-connection listening server based on coroutine runtime.
It does not support full parallell processing through threads or separate processes.

## Basic operation

Below will set up a rudimentary WebSocket server that listens to incoming text messages.
The added middlewares provide standard operability according to WebSocket protocol.

```php
$server = new WebSocket\Server();
$server
    // Add standard middlewares
    ->addMiddleware(new WebSocket\Middleware\CloseHandler())
    ->addMiddleware(new WebSocket\Middleware\PingResponder())
    // Listen to incoming Text messages
    ->onText(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Message $message) {
        // Act on incoming message
        echo "Got message: {$message->getContent()} \n";
        // Possibly respond to client
        $connection->text("I got your your message");
    })
    ->start();
```

## Configuration

The Server takes two arguments; port and ssl.
By default ssl is false. If port is not specified, it will use 80 for non-secure and 443 for secure server.
Other options are available runtime by calling configuration methods.

```php
// Secure server on port 8080
$server = new WebSocket\Server(ssl: true, port: 8080);
$server
    // Use a PSR-3 compatible logger
    ->setLogger(Psr\Log\LoggerInterface $logger)
    // Specify timeout in seconds (default 60 seconds)
    ->setTimeout(300)
    // Specify frame size in bytes (default 4096 bytes)
    ->setFrameSize(1024)
    ;

echo "port:         {$server->getPort()}\n";
echo "scheme:       {$server->getScheme()}\n";
echo "timeout:      {$server->getTimeout()}s\n";
echo "frame size:   {$server->getFrameSize()}b\n";
echo "running:      {$server->isRunning()}\n";
echo "connections:  {$server->getConnectionCount()}\n";
echo "ssl:          {$server->isSsl()}\n";
```

## Middlewares

Middlewares provide additional functionality when sending or receiving messages.
This repo comes with two middlewares that provide standard operability according to WebSocket protocol.

* CloseHandler - Automatically acts on incoming and outgoing Close requests, as specified in WebSocket protocol
* PingResponder - Responds with Pong message when receiving a Ping message, as specified in WebSocket protocol

If not added, you need to handle close operation and respond to ping requests in your own implementation.

```php
$server = new WebSocket\Server();
$server
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
All message listeners receive Server, Connection and Message as arguments.

```php
$server = new WebSocket\Server();
$server
    // Listen to incoming Text messages
    ->onText(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Text $message) {
        // Act on incoming message
    })
    // Listen to incoming Binary messages
    ->onBinary(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Binary $message) {
        // Act on incoming message
    })
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

## Sending a message to connected client

The Connection instance represents the client-server channel.
To send a message to a client, call the send() method on Connection instance with a Message instance.
Any of the five message types can be sent this way.

```php
$connection->send(new WebSocket\Message\Text("Server sends a message"));
$connection->send(new WebSocket\Message\Binary($binary));
$connection->send(new WebSocket\Message\Ping("My ping"));
$connection->send(new WebSocket\Message\Text("My pong"));
$connection->send(new WebSocket\Message\Close(1000, "Closing now"));
```
The are also convenience methods available for all types.
```php
$connection->text("Server sends a message");
$connection->binary($binary);
$connection->ping("My ping");
$connection->pong("My pong");
$connection->close(1000, "Closing now");
```

## Broadcasting message to all connects clients

The same send methods are available at Server instance.
Using these will send the message to all currently connected clients.

```php
$server->send(new WebSocket\Message\Text("Server sends a message"));
$server->send(new WebSocket\Message\Binary($binary));
$server->send(new WebSocket\Message\Ping("My ping"));
$server->send(new WebSocket\Message\Text("My pong"));
$server->send(new WebSocket\Message\Close(1000, "Closing now"));
```
Convenience methods available for all types.
```php
$server->text("Server sends a message");
$server->binary($binary);
$server->ping("My ping");
$server->pong("My pong");
$server->close(1000, "Closing now");
```

## Server control

When started, the server will continue to run until something tells it so stop.
There are some additional methods that control the server.

```php
// Start server - It will continuously listen to incoming messages and apply specified callback functions
$server->start();

// Stop server - When called, server will no longer listen to incoming messages but will not disconnect clients
$server->stop();

//Disconnect server - Server will immediately stop and disconnect all clients without normal close procedure
$server->disconnect();
```

To shut down server in an orderly fashion, you should first close all connected clients.

## Connection control

The Connection instance have some additional functions, besides sending messages to client.

```php
// Is connection open?
$connection->isConnected();

// Immediately disconnect client without normal close procedure
$connection->disconnect();

// Get local name for connection
$connection->getName();

// Get remote name for connection
$connection->getRemoteName();

// Get the Request client sent during handshake procedure
$connection->getHandshakeRequest();

// Get the Response server sent during handshake procedure
$connection->getHandshakeResponse();

// Trigger a tick event on connection
$connection->tick();

// Get and set associated meta data on connection
$connection->setMeta('myMetaData', $anything);
$connection->getMeta('myMetaData');
```
