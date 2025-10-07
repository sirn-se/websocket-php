[Documentation](Index.md) / Changelog

# Websocket: Changelog

## `v4.0`

 > PHP version `^8.1`

### `4.0.0`

 * Shared stream observer, sharable across multiple cients and servers (@sirn-se)
 * Configuration class for various settings (@sirn-se)
 * MessageEncodingException when compression fails (@sirn-se)
 * Many class local configuration setters removed (@sirn-se)
 * Remove deprecated code (@sirn-se)

## `v3.6`

 > PHP version `^8.1`

### `3.6.0`

 * CloseConnection and ReconnectException in Client listener (@sirn-se)
 * Creating PSR-7 messages using configurable PSR-17 factories  (@sirn-se)
 * v4 deprecation warnings (@sirn-se)
 * Improved phpdoc (@sirn-se)
 * Code coverage improvement (@sirn-se)

## `v3.5`

 > PHP version `^8.1`

### `3.5.0`

 * Timeouts support float (@sirn-se)
 * Client and Server start() can have specific timeout (@sirn-se)
 * Internal LoggerAwareTrait for logger handling (@sirn-se)
 * Constant in interface (@sirn-se)
 * Frame pulling rewritten (@sirn-se)
 * `phpstan` level 8, and fixes accordingly (@sirn-se)

## `v3.4`

 > PHP version `^8.1`

### `3.4.0`

 * Compression Extension as optional middleware (@sirn-se)
 * Per-Message Deflate as Compression implementation (@sirn-se)
 * Example: Server using SSL certificate capture (@marki555)

## `v3.3`

 > PHP version `^8.1`

### `3.3.0`

 * Using `Phrity/Net/Context` for context management in Client, Server and Connection (@sirn-se)
 * Additional input checks (@sirn-se)
 * Improved phpdoc and phpstan definitions (@sirn-se)
 * Improved logging: exceptions exposed in context (@sirn-se)
 * Extended documentation and examples (@sirn-se)

## `v3.2`

 > PHP version `^8.1`

### `3.2.6`

 * Fix for `3.2.5` (@carvefx)

### `3.2.5`

 * Allow additional content in Connection header during handshake - again (@sirn-se)

### `3.2.4`

 * Introducing ExceptionInterface as root to internal exceptions (@sirn-se)
 * Unresolvable errors must not trigger onError listener (@sirn-se)

### `3.2.3`

 * Allow empty reason phrase in HTTP request (@Sebastix)
 * Improved phpdoc and phpstan definitions (@sirn-se)
 * Support phpunit v12 (@sirn-se)

### `3.2.2`

 * Adding `phpstan` for static analysis (@sirn-se)

### `3.2.1`

 * Fix missing return statement in server shutdown method (@sirn-se)
 * Handle invalid server requests (@sirn-se)
 * Unit tests for php 8.5 (@sirn-se)
 * Documentation broken links fixed (@sirn-se)

### `3.2.0`

 * Server `setContext()` method (@sirn-se)
 * RSV flags on Frame (@sirn-se)
 * Fixed various typos and broken links (@pieterocp)
 * `phrity/net-stream v2.1` for context support and select warning handling (@sirn-se)
 * Deprecation warnings (@sirn-se)

## `v3.1`

 > PHP version `^8.1`

### `3.1.0`

 * Server `getConnections()`, `getReadableConnections()`, `getWritableConnections()` (@sirn-se)
 * `onHandshake(...)` listener (will deprecate `onConnect(...)`) (@sirn-se)
 * Server `setMaxConnections(int|null)` (@sirn-se)
 * Server `shutdown()` orderly close server (@sirn-se)
 * `phrity/net-uri v2.1` for URI decoding (@sirn-se)
 * Public class synopsis added to documentation (@sirn-se)

## `v3.0`

 > PHP version `^8.1`

### `3.0.0`

 * Support `psr/log v3` (@sirn-se)
 * Client `receive()` never return `null` (@sirn-se)
 * Typed class properties (@sirn-se)

## `v2.2`

 > PHP version `^8.0`

### `2.2.3`

 * URL-decoded user-info in headers (@dejanceltra)

### `2.2.2`

 * Fix so redirect to `https` uses `ssl` (ended up with `tcp` before) (@sirn-se)

### `2.2.1`

 * Minor fixes on  `FollowRedirect` middleware (@sirn-se)

### `2.2.0`

 * Optional `FollowRedirect` middleware (Client only) (@sirn-se)
 * Optional `SubprotocolNegotiation` middleware (@sirn-se)
 * `getMeta()` exposed on Client (@sirn-se)
 * Server throws `HandshakeException` if HTTP middleware return invalid status (@sirn-se)
 * New `ReconnectException` to force Client reconnection (@sirn-se)
 * `Server->isSsl()` method (@sirn-se)
 * Improved URI handling (@sirn-se)
 * Allow empty HTTP header handling (RFC compliance) (@sirn-se)
 * Documentation changes (@sirn-se)
 * Using `phrity/net v2` (@sirn-se)
 * Updating workflow and dependencies (@sirn-se)

## `v2.1`

 > PHP version `^8.0`

### `2.1.3`

 * Allow additional content in Connection header during handshake (@sirn-se)

### `2.1.2`

 * Allow repeated headers when pulling HTTP messages (@sirn-se)

### `2.1.1`

 * Fix issue with falsy but valid HTTP headers (@axklim)
 * Additional check for HTTP headers (@sirn-se)

### `2.1.0`

 * Http & Tick middleware support (@sirn-se)
 * PingInterval middleware for heartbeat functionality (@sirn-se)
 * Connection get/set meta functions (@sirn-se)
 * All classes Stringable, consistent (@sirn-se)
 * HTTP method fix (@sirn-se)
 * Unit tests for php 8.4 (@sirn-se)
 * Fixing various typos (@UksusoFF, @sirn-se)
 * Remove unused code and documentation (@sirn-se)

## `v2.0`

 > PHP version `^8.0`

### `2.0.1`

 * Fix `psr/log` dependency (@sirn-se)

### `2.0.0`

 * Listeners for client and server (@sirn-se)
 * Middleware support (@sirn-se)
 * Multi connection server (@sirn-se)
 * `receive()` always return Message instance or null (@sirn-se)
 * `send()` require Message instance as first argument (@sirn-se)
 * Strict mask policy (@sirn-se)
 * Strict handshake procedure (@sirn-se)
 * `Close` get close-status methods (@sirn-se)
 * Server no longer auto-increment port (@sirn-se)
 * Removed deprecated methods and options (@sirn-se)
 * Moved source (@sirn-se)
 * Removed PHP `7.4` support (@sirn-se)

## `v1.7`

 > PHP version `^7.4|^8.0`

### `1.7.3`

 * Fix dependency `psr/http-message` (@sirn-se)

### `1.7.2`

 * PSR compliance `psr/log v3` `psr/http-message v2` (@sirn-se)

### `1.7.1`

 * Define return on receive (@zgrguric, @sirn-se)

### `1.7.0`

 * Client `getHandshakeResponse()` method (@sirn-se)
 * Server `getHandshakeRequest()` method (@sirn-se)
 * `connect()` methods are now public (@sirn-se)
 * Modularized design (@sirn-se)
 * Using managed streams (@sirn-se)
 * Various code improvements (@sirn-se)
 * Unit test rewrite (@sirn-se)
 * Deprecations for v2.0 (@sirn-se)

## `v1.6`

 > PHP version `^7.4|^8.0`

### `1.6.4`

 * Masking policy according to specification (@sirn-se)

### `1.6.3`

 * Fix issue with implicit default ports (@etrinh, @sirn-se)

### `1.6.2`

 * Fix issue where port was missing in socket uri (@sirn-se)

### `1.6.1`

 * Fix client path for http request (@simPod, @sirn-se)

### `1.6.0`
 * Connection separate from Client and Server (@sirn-se)
 * getPier() deprecated, replaced by getRemoteName() (@sirn-se)
 * Client accepts `Psr\Http\Message\UriInterface` as input for URI:s (@sirn-se)
 * Bad URI throws exception when Client is instanciated, previously when used (@sirn-se)
 * Preparations for multiple conection and listeners (@sirn-se)
 * Major internal refactoring (@sirn-se)

## `v1.5`

 > PHP version `^7.2|^8.0`

### `1.5.8`

 * Handle read error during handshake (@sirn-se)

### `1.5.7`

 * Large header block fix (@sirn-se)

### `1.5.6`

 * Add test for PHP 8.1 (@sirn-se)
 * Code standard (@sirn-se)

### `1.5.5`

 * Support for psr/log v2 and v3 (@simPod)
 * GitHub Actions replaces Travis (@sirn-se)

### `1.5.4`

 * Keep open connection on read timeout (@marcroberts)

### `1.5.3`

 * Fix for persistent connection (@sirn-se)

### `1.5.2`

 * Fix for getName() method (@sirn-se)

### `1.5.1`

 * Fix for persistent connections (@rmeisler)

### `1.5.0`

 * Convenience send methods; text(), binary(), ping(), pong() (@sirn-se)
 * Optional Message instance as receive() method return (@sirn-se)
 * Opcode filter for receive() method (@sirn-se)
 * Added PHP `8.0` support (@webpatser)
 * Dropped PHP `7.1` support (@sirn-se)
 * Fix for unordered fragmented messages (@sirn-se)
 * Improved error handling on stream calls (@sirn-se)
 * Various code re-write (@sirn-se)

## `v1.4`

 > PHP version `^7.1`

### `1.4.3`

 * Solve stream closure/get meta conflict (@sirn-se)
 * Examples and documentation overhaul (@sirn-se)

### `1.4.2`

 * Force stream close on read error (@sirn-se)
 * Authorization headers line feed (@sirn-se)
 * Documentation (@matias-pool, @sirn-se)

### `1.4.1`

 * Ping/Pong, handled internally to avoid breaking fragmented messages (@nshmyrev, @sirn-se)
 * Fix for persistent connections (@rmeisler)
 * Fix opcode bitmask (@peterjah)

### `1.4.0`

 * Dropped support of old PHP versions (@sirn-se)
 * Added PSR-3 Logging support (@sirn-se)
 * Persistent connection option (@slezakattack)
 * TimeoutException on connection time out (@slezakattack)

## `v1.3`

 > PHP version `^5.4` and `^7.0`

### `1.3.1`

 * Allow control messages without payload (@Logioniz)
 * Error code in ConnectionException (@sirn-se)

### `1.3.0`

 * Implements ping/pong frames (@pmccarren @Logioniz)
 * Close behaviour (@sirn-se)
 * Various fixes concerning connection handling (@sirn-se)
 * Overhaul of Composer, Travis and Coveralls setup, PSR code standard and unit tests (@sirn-se)

## `v1.2`

 > PHP version `^5.4` and `^7.0`

### `1.2.0`

 * Adding stream context options (to set e.g. SSL `allow_self_signed`).

## `v1.1`

 > PHP version `^5.4` and `^7.0`

### `1.1.2`

 * Fixed error message on broken frame.

### `1.1.1`

 * Adding license information.

### `1.1.0`

 * Supporting huge payloads.

## `v1.0`

 > PHP version `^5.4` and `^7.0`

### `1.0.3`

 * Bugfix: Correcting address in error-message

### `1.0.2`

 * Bugfix: Add port in request-header.

### `1.0.1`

 * Fixing a bug from empty payloads.

### `1.0.0`

 * Release as production ready.
 * Adding option to set/override headers.
 * Supporting basic authentication from user:pass in URL.

