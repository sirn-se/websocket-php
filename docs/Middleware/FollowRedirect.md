[Documentation](../Index.md) / [Middleware](../Middleware.md) / FollowRedirect

# Websocket: FollowRedirect middleware

This middlewares is included in library and can be added to provide additional functionality.

Can only be added to Client.
During handshake, it reacts to `3xx` HTTP status and reconnect the Client to provided location.

## Client

Will follow redirect by setting new URI and reconnection the Client.

* Server response during handshake have a `3xx` status
* Server response also includes a `Location` header
* Maximum number of redirects has not been exceeded

```php
$client->addMiddleware(new WebSocket\Middleware\FollowRedirect());
$client->connect();
```

## Maximum number of redirects

By default, maximum number of redirects in `10`.
If middleware receive additional redirect instructions after that, it will throw a HandshakeException.

It is also possible to specify maximum number of redirects as parameter.

```php
// Allow 20 redirects
$client->addMiddleware(new WebSocket\Middleware\FollowRedirect(20);
```
