[Documentation](Index.md) > Middleware

# Websocket: Middleware

Both [Client](Client.md) and [Server](Server.md) support adding middlewares.
All added middlewares will be called when a [Message](Message.md) is sent and/or received.

## Standard operation

These two middlewares provide standard operability according to WebSocket protocol,
and should be added unless you write your own implementation of close and ping/pong handling.

* `CloseHandler` - Automatically acts on incoming and outgoing Close requests, as specified in WebSocket protocol
* `PingResponder` - Responds with Pong message when receiving a Ping message, as specified in WebSocket protocol

Add middlewares by calling the `addMiddleware` method.

```php
$client
    ->addMiddleware(new WebSocket\Middleware\CloseHandler())
    ->addMiddleware(new WebSocket\Middleware\PingResponder())
    ;

$server
    ->addMiddleware(new WebSocket\Middleware\CloseHandler())
    ->addMiddleware(new WebSocket\Middleware\PingResponder())
    ;
```

## The CloseHandler middleware

* When a Close message is received, CloseHandler will respond with a Close confirmation message
* When a Close confirmation message is received, CloseHandler will close the connection
* When a Close message is sent, CloseHandler will block further messages from being sent

## The PingResponder middleware

* When a Ping message is received, PingResponder will respond with a Pong message

## The Callback middleware

This middleware will apply callback functions when a message is sent and/or received.

```php
$client_or_server
    ->addMiddleware(new WebSocket\Middleware\Callback(
        incoming: function (WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection) {
            $message = $stack->handleIncoming(); // Get incoming message from next middleware
            $message->setContent("Changed message");
            return $message;
        },
        outgoing: function (WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection, WebSocket\Message\Message $message) {
            $message->setContent('Changed message');
            $message = $stack->handleOutgoing($message); // Forward outgoing message to next middleware
            return $message;
        },
        httpIncoming: function (WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection) {
            $message = $stack->handleHttpIncoming(); // Get incoming message from next middleware
            return $message;
        },
        httpOutgoing: function (WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection, WebSocket\Message\Message $message) {
            $message = $stack->handleHttpOutgoing($message); // Forward outgoing message to next middleware
            return $message;
        }
    ));
```

The callback functions **MUST** return a [Message](Message.md) instance or a HTTP request/response message respectively..

The `handleIncoming`, `handleOutgoing`, `handleHttpIncoming` and `handleHttpOutgoing` methods will pass initiative further down the middleware stack.

## Writing your own middleware

A middleware **MUST** implement the `MiddlewareInterface`.

```php
interface WebSocket\Middleware\MiddlewareInterface
{
    public function __toString(): string;
}
```

A middleware that wants to handle incoming messages **MUST** implement the `ProcessIncomingInterface`.

```php
interface WebSocket\Middleware\ProcessIncomingInterface extends WebSocket\Middleware\MiddlewareInterface
{
    public function processIncoming(
        WebSocket\Middleware\ProcessStack $stack,
        WebSocket\Connection $connection
    ): WebSocket\Message\Message;
}
```

A middleware that wants to handle outgoing messages **MUST** implement the `ProcessOutgoingInterface`.

```php
interface WebSocket\Middleware\ProcessOutgoingInterface extends WebSocket\Middleware\MiddlewareInterface
{
    public function processOutgoing(
        WebSocket\Middleware\ProcessStack $stack,
        WebSocket\Connection $connection,
        WebSocket\Message\Message $message
    ): WebSocket\Message\Message;
}
```

A middleware that wants to handle incoming HTTP messages **MUST** implement the `ProcessHttpIncomingInterface`.

```php
interface WebSocket\Middleware\ProcessHttpIncomingInterface extends WebSocket\Middleware\MiddlewareInterface
{
    public function processHttpIncoming(
        WebSocket\Middleware\ProcessHttpStack $stack,
        WebSocket\Connection $connection
    ): WebSocket\Http\Message;
}
```

A middleware that wants to handle outgoing HTTP messages **MUST** implement the `ProcessHttpOutgoingInterface`.

```php
interface WebSocket\Middleware\ProcessHttpOutgoingInterface extends WebSocket\Middleware\MiddlewareInterface
{
    public function processHttpOutgoing(
        WebSocket\Middleware\ProcessHttpStack $stack,
        WebSocket\Connection $connection,
        WebSocket\Http\Message $message
    ): WebSocket\Http\Message;
}
```

The `ProcessStack` and `ProcessHttpStack` classes are used to hand over initiative to the next middleware in middleware stack.

```php
// Get the received Message, possibly handled by other middlewares
$message = $stack->handleIncoming();

// Forward the Message to be sent, possibly handled by other middlewares
$message = $stack->handleOutgoing($message);

// Get the received HTTP request/response message, possibly handled by other middlewares
$message = $stack->handleHttpIncoming();

// Forward the HTTP request/response message to be sent, possibly handled by other middlewares
$message = $stack->handleHttpOutgoing($message);

```
