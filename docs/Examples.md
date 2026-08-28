[Documentation](Index.md) / Examples

# Websocket: Examples

Here are some examples on how to use the WebSocket library.

##  Logger

Client and Server accepts a [PSR-3](https://www.php-fig.org/psr/psr-3/) compatible logger to be attached.

The `phrity/logger-console` in example is useful for debugging.
For production, use a logger that stores or accumulates log data.

```php
$logger = new Phrity\Logger\Console\ConsoleLogger(Phrity\Logger\Console\Verbosity::Debug);

$client = new WebSocket\Client('wss://echo.websocket.org/');
$client->setLogger($logger);

$server = new WebSocket\Server();
$server->setLogger($logger);
```

An example of server output;
```
info | Server listening to port 80
debug | Wrote 129 of 129 bytes.
info | Server connected to port 80
info | Received 'text' message
debug | Wrote 9 of 9 bytes.
info | Sent 'text' message
debug | Received 'close', status: 1000.
debug | Wrote 32 of 32 bytes.
info | Sent 'close' message
info | Received 'close' message
```

## Self-resuming continuous subscription Client

This setup will create Client that sends initial message to Server,
and then subscribes to messages sent by Server.
The `PingInterval` (possibly change interval) will keep connection open.
If something goes wrong, it will in most cases be able to re-connect and resume subscription.

```php
use Psr\Http\Message\{
    ResponseInterface,
    RequestInterface,
};
use WebSocket\Client;
use WebSocket\Connection;
use WebSocket\Exception\ExceptionInterface;
use WebSocket\Message\Text;
use WebSocket\Middleware\{
    CloseHandler,
    PingResponder,
    PingInterval,
};

// Create client
$client = new Client("wss://echo.websocket.org/");
$client
    // Add standard middlewares
    ->addMiddleware(new CloseHandler())
    ->addMiddleware(new PingResponder())
    // Add ping interval middleware as heartbeat to keep connection open
    ->addMiddleware(new PingInterval(interval: 30))
    // Timeout should have same (or lower) value as interval
    ->setTimeout(30)
    ->onHandshake(function (Client $client, Connection $connection, RequestInterface $request, ResponseInterface $response) {
        // Initial message, typically some authorization or configuration
        // This will be called everytime the client connect or reconnect
        $client->text($initial_message);
    })
    ->onText(function (Client $client, Connection $connection, Text $message) {
        // Act on incoming message
        $message->getContent();
        // Possibly respond to server
        $client->text($some_message);
    })
    ->onError(function (Client $client, Connection|null $connection, ExceptionInterface $exception) {
        // Act on exception
        if (!$client->isRunning()) {
            // Re-start if not running - will reconnect if necessary
            $client->start();
        }
    })
    // Start subscription
    ->start()
    ;
```

## Running Client as Fiber

When your application need to switch code context, using [Fibers](https://www.php.net/manual/en/class.fiber.php) for coroutine management can be a good idea. It is fully possible to run the Client as a Fiber.

```php
use Fiber;
use WebSocket\Client;
use WebSocket\Connection;
use WebSocket\Message\Text;
use WebSocket\Middleware\{
    CloseHandler,
    PingResponder,
};

// Create client
$client = new Client("wss://echo.websocket.org/");
$client
    // Add standard middlewares
    ->addMiddleware(new CloseHandler())
    ->addMiddleware(new PingResponder())
    // Timeout should have a pretty low value
    ->setTimeout(1)
    ->onText(function (Client $client, Connection $connection, Text $message) {
        echo "> Received '{$message->getContent()}' [opcode: {$message->getOpcode()}]\n";
    })
    ->onTick(function () {
        Fiber::suspend(); // Suspend after each listener loop
    })
    ;

// Create array with fibers
$fibres = [
    'listener' => new Fiber(function () use ($client) {
        $client->start(); // Start listener
    }),
    'sender' => new Fiber(function () use ($client) {
        while ($client->isWritable()) {
            if (rand(0, 10) == 0) {
                // Random close
                $message = $client->close(); // Send a message
                echo "< Sent '{$message->getContent()}' [opcode: {$message->getOpcode()}]\n";
                return; // Return from function to terminate
            }

            $message = $client->text("My message " . rand(1000, 9999)); // Send a message
            echo "< Sent '{$message->getContent()}' [opcode: {$message->getOpcode()}]\n";
            Fiber::suspend(); // Suspend after each sent mesage
        }
    }),
    // + Any other Fiber you need to run
];

// Switching context by looping Fibers
do {
    foreach ($fibres as $name => $fiber) {
        echo sprintf(
            "Running fiber: %s [started: %s, running: %s, suspended: %s, terminated: %s]\n",
            $name,
            json_encode($fiber->isStarted()),
            json_encode($fiber->isRunning()),
            json_encode($fiber->isSuspended()),
            json_encode($fiber->isTerminated()),
        );
        if ($fiber->isRunning() || $fiber->isTerminated()) {
            continue;
        }
        if ($fiber->isSuspended()) {
            $fiber->resume(); // Resume if suspended
            continue;
        }
        $fiber->start(); // Start if not started
    }
} while ($client->isRunning());
```

## Server using SSL certificate capture

By setting `capture_peer_cert` the Server will capture SSL certificates.
Example code set context on Server and verify the certificate during Handshake.

Example provided by [marki555](https://github.com/marki555).

```php
use Psr\Http\Message\{
    ResponseInterface,
    RequestInterface,
};
use WebSocket\Connection;
use WebSocket\Exception\CloseException;
use WebSocket\Message\Text;
use WebSocket\Middleware\{
    CloseHandler,
    PingResponder,
};
use WebSocket\Server;

$port = 443; // Port to use
$logger = new Logger(); // PSR-3 Logger to use

// Create context
$context = new Phrity\Net\Context();
// Set up SSL with CA certificate
$context->setOptions(['ssl' => [
    'local_cert'        => 'certs/CA/acs-ws-server.crt',
    'local_pk'          => 'certs/CA/acs-ws-server.key',
    'cafile'            => 'certs/CA/ca.crt',
    'verify_peer'       => true, // if false, accept SSL handshake without client certificate
    'verify_peer_name'  => false,
    'allow_self_signed' => false,
    'capture_peer_cert' => true,
]]);

// Create server
$server = new Server(port: $port, ssl: true);
$server
    // Add the context
    ->setContext($context)
    // Add standard middlewares
    ->addMiddleware(new CloseHandler())
    ->addMiddleware(new PingResponder())
    // Set logger
    ->setLogger($logger)
    // Resolve cerificate during Handshake
    ->onHandshake(function (Server $server, Connection $connection, RequestInterface $request, ResponseInterface $response) use ($logger) {
        $context = $connection->getContext();
        $certificate = $context->getOption('ssl','peer_certificate');
        $cn = openssl_x509_parse($certificate)['subject']['CN'];
        $user = myUserVerification($cn); // Verify user using yout own code
        if (!$user) {
            $logger->warning("[{$conn->getRemoteName()}] Client authentication failed, unknown CN: {$cn}");
            throw new CloseException(Client Auth failed', context: ['status' => 1008]);  // CLOSE_POLICY_VIOLATION
        }
        $logger->info("[{$conn->getRemoteName()}] Client authenticated as '{$user}'");
        $connection->setMeta('user', $user); // Store for later use
    })
    ->onText(function (Server $server, Connection $connection, Text $message) use ($logger) {
        $logger->info("[{$conn->getMeta('user')}] Rcvd: {$message->getContent()}");
    })
    // Start listening to incoming traffic
    ->start()
    ;
```

## The `send` client

Source: [examples/send.php](https://github.com/sirn-se/websocket-php/blob/v3.4-main/examples/send.php)

A simple, single send/receive client.

Example use:
```bash
php examples/send.php --opcode text "A text message" # Send a text message to localhost
php examples/send.php --opcode ping "ping it" # Send a ping message to localhost
php examples/send.php --uri wss://echo.websocket.org "A text message" # Send a text message to echo.websocket.org
php examples/send.php --opcode text --debug "A text message" # Use runtime debugging
```

## The `echoserver` server

Source: [examples/echoserver.php](https://github.com/sirn-se/websocket-php/blob/v3.4-main/examples/echoserver.php)

A simple server that responds to received commands.

Example use:
```bash
php examples/echoserver.php # Run with default settings
php examples/echoserver.php --port 8080 # Listen on port 8080
php examples/echoserver.php --debug #  Use runtime debugging
```

These strings can be sent as message to trigger server to perform actions;
* `@close` -  Server will close current connection
* `@ping` - Server will send a ping message on current connection
* `@disconnect` - Server will disconnect current connection
* `@info` - Server will respond with connection info
* `@server-stop` - Server will stop listening
* `@server-close` - Server will close all connections
* `@server-ping` - Server will send a ping message on all connections
* `@server-disconnect` - Server will disconnect all connections
* `@server-info` - Server will respond with server info
* For other sent strings, server will respond with the same strings

## The `random` client

Source: [examples/random_client.php](https://github.com/sirn-se/websocket-php/blob/v3.4-main/examples/random_client.php)

The random client will use random options and continuously send/receive random messages.

Example use:
```bash
php examples/random_client.php --uri wss://echo.websocket.org # Connect to echo.websocket.org
php examples/random_client.php --timeout 5 --framesize 16 # Specify settings
php examples/random_client.php --debug #  Use runtime debugging
```

## The `random` server

Source: [examples/random_server.php](https://github.com/sirn-se/websocket-php/blob/v3.4-main/examples/random_server.php)

The random server will use random options and continuously send/receive random messages.

Example use:
```bash
php examples/random_server.php --port 8080 # Listen on port 8080
php examples/random_server.php --timeout 5 --framesize 16 # Specify settings
php examples/random_server.php --debug #  Use runtime debugging
```

## The `delegating` server

Source: [examples/delegating_server.php](https://github.com/sirn-se/websocket-php/blob/v3.4-main/examples/delegating_server.php)

By using context switching, the server will act as a proxy and forward incoming messages to a remote server.
The example is a 1:1 setup, Server will only initiate a single Client and broadcast messages read from upstream to all connected clients.
Please note that switching between listening context can appear slow.
If you need better performance, use separate scripts for client and server and some kind of intermediate to connect them.

Example use:
```bash
php examples/delegating_server.php --uri=ws://example-server.com --port 8080 # Listen on port 8080
php examples/delegating_server.php --uri=ws://example-server.com --timeout 5 --framesize 16 # Specify settings
php examples/delegating_server.php --uri=ws://example-server.com --debug # Use runtime debugging
```

Try it:
```bash
# Start remote server on 8000
php examples/echoserver.php --port 8000
# Start delegating server on port 8001, with above echoserver as remote
php examples/delegating_server.php --remote=ws://127.0.0.1:8000 --port=8001
# Send message to delegating server
php examples/send.php --uri=ws://127.0.0.1:8001 "A message to delegate"
```