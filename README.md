<p align="center"><img src="docs/logotype.png" alt="Phrity Websocket" width="100%"></p>

# Websocket Client and Server for PHP

[![Build Status](https://github.com/sirn-se/websocket-php/actions/workflows/acceptance.yml/badge.svg)](https://github.com/sirn-se/websocket-php/actions)
[![Coverage Status](https://coveralls.io/repos/github/sirn-se/websocket-php/badge.svg?branch=v2.0-main)](https://coveralls.io/github/sirn-se/websocket-php)

This library contains WebSocket client and server for PHP.

The client and server provides methods for reading and writing to WebSocket streams.

This repo replaces the abandoned `textalk/websocket` repo
and is maintained by Sören Jensen, who has been maintaining the original since `v1.3`.

## Some features

* Client and multi-connection Server
* `ws` (TCP) and `wss` (SSL) support
* Listener callbacks on incoming messages and other events
* Close and Ping/Pong handling (standard middlewares)
* Deflate compression (middleware)
* Additional optional middlewares and ability to create own middlewares
* Support message fragmentation and payload masking

## Documentation

* [Documentation](docs/Index.md)
* [Client](docs/Client.md) - The WebSocket client
* [Server](docs/Server.md) - The WebSocket server
* [Changelog](docs/Changelog.md) - The changelog of this repo
* [Contributing](docs/Contributing.md) - Contributors and requirements
* [Examples](docs/Examples.md) - Examples
* [v2 -> v3](docs/Migrate_2_3.md) - How to migrate from v2 to v3
* [v3 -> v4](docs/Migrate_3_4.md) - How to migrate from v3 to v4

## Installing

Preferred way to install is with [Composer](https://getcomposer.org/).
```
composer require phrity/websocket
```

## Client

The [client](docs/Client.md) can read and write on a WebSocket stream.
It internally supports Upgrade handshake and implicit close and ping/pong operations.

Set up a WebSocket Client for request/response strategy.
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

Set up a WebSocket Client for continuous subscription
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


## Server

The [server](docs/Server.md) is a multi connection, listening server.
It internally supports Upgrade handshake and implicit close and ping/pong operations.

Set up a WebSocket Server for continuous listening
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

### License

[ISC License](LICENSE.md)

