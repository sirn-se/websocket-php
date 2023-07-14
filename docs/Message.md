[Client](Client.md) • [Server](Server.md) • Message • [Classes](Classes.md) • [Examples](Examples.md) • [Changelog](Changelog.md) • [Contributing](Contributing.md)

# Websocket: Messages

If option `return_obj` is set to `true` on [client](Client.md) or [server](Server.md),
the `receive()` method will return a Message instance instead of a string.
The `send()` method also accepts a Message instance.

Available classes correspond to opcode;
* WebSocket\Message\Text
* WebSocket\Message\Binary
* WebSocket\Message\Ping
* WebSocket\Message\Pong
* WebSocket\Message\Close

## Example

Sneding and eceving a Message and echo some methods.

```php
$client = new WebSocket\Client('ws://echo.websocket.org/', ['return_obj' => true]);

// Send messages
$client->send(new WebSocket\Message\Text('Hello WebSocket.org!'));

// Echo return same message as sent
$message = $client->receive();
echo $message->getOpcode(); // -> "text"
echo $message->getLength(); // -> 20
echo $message->getContent(); // -> "Hello WebSocket.org!"
echo $message->hasContent(); // -> true
echo $message->getTimestamp()->format('H:i:s'); // -> 19:37:18
$client->close();
```
