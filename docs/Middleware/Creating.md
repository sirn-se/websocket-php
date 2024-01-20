[Documentation](../Index.md) / [Middleware](../Middleware.md) / Creating

# Websocket: Creating your own middleware

You can create your own middleware by implementing relevant interfaces.
A middleware may handle WebSocket message transfers, HTTP handshake operations, and Tick operability.

A middleware **MUST** implement the `MiddlewareInterface`.

```php
interface WebSocket\Middleware\MiddlewareInterface
{
    public function __toString(): string;
}
```

## WebSocket message transfer

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

## HTTP handshake operations

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

## Tick operability

A middleware that wants to handle Tick operation **MUST** implement the `ProcessTickInterface`.

```php
interface WebSocket\Middleware\ProcessTickInterface extends WebSocket\Middleware\MiddlewareInterface
{
    public function processTick(
        WebSocket\Middleware\ProcessTickStack $stack,
        WebSocket\Connection $connection
    ): void;
}
```

## Working with middleware stacks

The `ProcessStack`, `ProcessHttpStack` and `ProcessTickStack` classes are used to hand over initiative to the next middleware in middleware stack.

```php
// Get the received Message, possibly handled by other middlewares
$message = $stack->handleIncoming();

// Forward the Message to be sent, possibly handled by other middlewares
$message = $stack->handleOutgoing($message);

// Get the received HTTP request/response message, possibly handled by other middlewares
$message = $stack->handleHttpIncoming();

// Forward the HTTP request/response message to be sent, possibly handled by other middlewares
$message = $stack->handleHttpOutgoing($message);

// Forward the Tick operation, possibly handled by other middlewares
$stack->handleTick();
```
