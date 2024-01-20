[Documentation](Index.md) / [Middleware](Middleware.md) / Callback

# Websocket: Callback middleware

This middleware will apply callback functions on certain actions.
This middlewares is included in library and can be added to provide additional functionality.

```php
$client_or_server
    ->addMiddleware(new WebSocket\Middleware\Callback(
        incoming: function (WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection): WebSocket\Message\Message {
            $message = $stack->handleIncoming(); // Get incoming message from next middleware
            $message->setContent("Changed message");
            return $message;
        },
        outgoing: function (WebSocket\Middleware\ProcessStack $stack, WebSocket\Connection $connection, WebSocket\Message\Message $message): WebSocket\Message\Message {
            $message->setContent('Changed message');
            $message = $stack->handleOutgoing($message); // Forward outgoing message to next middleware
            return $message;
        },
        httpIncoming: function (WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection): WebSocket\Http\Message {
            $message = $stack->handleHttpIncoming(); // Get incoming message from next middleware
            return $message;
        },
        httpOutgoing: function (WebSocket\Middleware\ProcessHttpStack $stack, WebSocket\Connection $connection, WebSocket\Http\Message $message): WebSocket\Http\Message {
            $message = $stack->handleHttpOutgoing($message); // Forward outgoing message to next middleware
            return $message;
        },
        tick: function (WebSocket\Middleware\ProcessTickStack $stack, WebSocket\Connection $connection): void {
            $stack->handleTick(); // Forward tick to next middleware
        }
    ));
```

* The `incoming` and `outgoing` callbacks **MUST** return a [Message](Message.md) instance
* The `httpIncoming` and `httpOutgoing` callbacks **MUST** return a HTTP request/response instance respectively
* The `tick` callback returns nothing

The `handleIncoming`, `handleOutgoing`, `handleHttpIncoming`, `handleHttpOutgoing` and `handleTick` methods will pass initiative further down the middleware stack.
