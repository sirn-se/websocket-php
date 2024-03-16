[Documentation](../Index.md) / [Middleware](../Middleware.md) / SubprotocolNegotiation

# Websocket: SubprotocolNegotiation middleware

This middlewares is included in library and can be added to provide additional functionality.

It can be added to both Client and Server to help negotiate subprotocol to use.
Note: This Middleware only negotiate protocols, it does NOT implement any subprotocols.

## Client

When used on Client, it will send a list of requested subprotocols to the Server.
The Server is then expected to respond with the first requested subprotocol it supports, if any.
The Client MUST expect Server to send messages according to selected subprotocol.

```php
$client->addMiddleware(new WebSocket\Middleware\SubprotocolNegotiation([
    'subproto-1',
    'subproto-2',
    'subproto-3',
]));
$client->connect();
$selected_subprotocol = $this->client->getMeta('subprotocolNegotiation.selected');
```

## Server

When added on Server, it should be defined with a list of subprotocols that Server support.
When Client request subprotocols, it will select the first requested protocol available in the list.
The ClienServert MUST expect Client to send messages according to selected subprotocol.

```php
$server->addMiddleware(new WebSocket\Middleware\SubprotocolNegotiation([
    'subproto-1',
    'subproto-2',
    'subproto-3',
]));
$server->->onText(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Message $message) {
    $selected_subprotocol = $connection->getMeta('subprotocolNegotiation.selected');
})->start();
```

## Require option

If second parameter is set to `true` a failed negotiation will close connection.

* When used on Client, this will cause a `HandshakeException`.
* When used on Server, server will respond with `426 Upgrade Required` HTTP error status.

```php
$client->addMiddleware(new WebSocket\Middleware\SubprotocolNegotiation([
    'subproto-1',
    'subproto-2',
    'subproto-3',
], true));
```
