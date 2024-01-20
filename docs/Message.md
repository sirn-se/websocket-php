[Documentation](Index.md) / Message

# Websocket: Message

WebSocket messages comes as any of five types; Text, Binary, Ping, Pong and Close.
The type is defined as opcode in WebSocket standard, and each classname corresponds to current message opcode.

Text and Binary are the main content message. The others are used for internal communication and typically do not contain content.
All provide the same methods, except Close that have an additional method not present on other types of messages.

## Creating Message instance

```php
// Text and Binary message types
$text = new WebSocket\Message\Text("Some text to be sent");
$binary = new WebSocket\Message\Binary("<binary string here>");

// Ping and Pong may optionally have content
$ping = new WebSocket\Message\Ping();
$ping = new WebSocket\Message\Ping("Some text");
$pong = new WebSocket\Message\Pong();
$pong = new WebSocket\Message\Pong("Some text");

// Close may optionally have close status and content
$close = new WebSocket\Message\Close();
$close = new WebSocket\Message\Close(1000);
$close = new WebSocket\Message\Close(1000, "Some text");
```

## General methods

These methods are available on all Message types

```php
// Opcode as "text", "binary", "ping", "pong" or "close"
echo $message->getOpcode();

// Character length of message content
echo $message->getLength();

// Has message content
echo $message->hasContent();

// Get message content
echo $message->getContent();

// Set message content
echo $message->setContent("Some text");

// Get DateTime of message
echo $message->getTimestamp()->format('H:i:s');
```

## Close methods

The Close message has additional methods.

```php
// Get close status
echo $message->getCloseStatus();

// Set close status
echo $message->setCloseStatus(1000);
```

## How Message instance is used

```php
// Client sending Message to server
$client->send(new WebSocket\Message\Text("Some text to be sent"));

// Client receiving Message from server
$message = $client->receive();

// Client listen to messages of Text type
$client->onText(function ($client, $connection, $message) {

    // Client sending Message to server
    $client->send(new WebSocket\Message\Text("Some text to be sent"));
});
```

```php
// Server broadcasting Message to all connected clients
$server->send(new WebSocket\Message\Text("Some text to be sent"));

// Server listen to messages of Text type
$server->onText(function ($server, $connection, $message) {

    // Server sending Message to specific client
    $connection->send(new WebSocket\Message\Text("Some text to be sent"));

    // Server broadcasting Message to all connected clients
    $server->send(new WebSocket\Message\Text("Some text to be sent"));
});
```