# Websocket Client and Server for PHP

[![Build Status](https://github.com/sirn-se/websocket-php/actions/workflows/acceptance.yml/badge.svg)](https://github.com/sirn-se/websocket-php/actions)
[![Coverage Status](https://coveralls.io/repos/github/sirn-se/websocket-php/badge.svg?branch=v2.0-main)](https://coveralls.io/github/sirn-se/websocket-php)

This library contains WebSocket client and server for PHP.
Replaces `textalk/websocket`.

The client and server provides methods for reading and writing to WebSocket streams.

This fork is maintained by Sören Jensen, who has been maintaining the original textalk/websocket
repo since `v1.3`.

## Documentation

* [Documentation](docs/Index.md)
* [Client](docs/Client.md) - The WebSocket client
* [Server](docs/Server.md) - The WebSocket server
* [Changelog](docs/Changelog.md) - The changelog of this repo
* [Contributing](docs/Contributing.md) - Contributors and requirements
* [Examples](docs/Examples.md) - Contributors and requirements

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

Set up a WebSocket Client for continuous subscription
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

[ISC License](COPYING.md)

