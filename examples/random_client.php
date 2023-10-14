<?php

/**
 * Websocket client that read/write random data.
 * Run in console: php examples/random_client.php
 *
 * Console options:
 *  --uri <uri> : The URI to connect to, default ws://localhost:8000
 *  --timeout <int> : Timeout in seconds, random default
 *  --framesize <int> : Frame size as bytes, random default
 *  --debug : Output log data (if logger is available)
 */

namespace WebSocket;

require __DIR__ . '/../vendor/autoload.php';

error_reporting(-1);

$randStr = function (int $maxlength = 4096) {
    $string = '';
    $length = rand(1, $maxlength);
    for ($i = 0; $i < $length; $i++) {
        $string .= chr(rand(33, 126));
    }
    return $string;
};

echo "# Random client\n";

// Initiate client.
while (true) {
    // Server options specified or random
    $options = array_merge([
        'uri'       => 'ws://localhost:80',
        'timeout'   => rand(1, 60),
        'framesize' => rand(1, 4096) * 8,
    ], getopt('', ['uri:', 'timeout:', 'framesize:', 'debug']));

    try {
        $client = new Client($options['uri']);
        $client
            ->addMiddleware(new \WebSocket\Middleware\CloseHandler())
            ->addMiddleware(new \WebSocket\Middleware\PingResponder())
            ;

        // If debug mode and logger is available
        if (isset($options['debug']) && class_exists('WebSocket\Test\EchoLog')) {
            $client->setLogger(new \WebSocket\Test\EchoLog());
            echo "# Using logger\n";
        }
        if (isset($options['timeout'])) {
            $client->setTimeout($options['timeout']);
            echo "# Set timeout: {$options['timeout']}\n";
        }
        if (isset($options['framesize'])) {
            $client->setFrameSize($options['framesize']);
            echo "# Set frame size: {$options['framesize']}\n";
        }

        echo "# Listening on {$options['uri']}\n";
        $client->onConnect(function ($client, $connection, $handshake) {
            echo "> [{$connection->getRemoteName()}] Server connected {$handshake->getStatusCode()}\n";
        })->onDisconnect(function ($client, $connection) {
            echo "> [{$connection->getRemoteName()}] Server disconnected\n";
        })->onText(function ($client, $connection, $message) {
            echo "> [{$connection->getRemoteName()}] Received [{$message->getOpcode()}]\n";
        })->onBinary(function ($client, $connection, $message) {
            echo "> [{$connection->getRemoteName()}] Received [{$message->getOpcode()}]\n";
        })->onPing(function ($client, $connection, $message) {
            echo "> [{$connection->getRemoteName()}] Received [{$message->getOpcode()}]\n";
        })->onPong(function ($client, $connection, $message) {
            echo "> [{$connection->getRemoteName()}] Received [{$message->getOpcode()}]\n";
        })->onClose(function ($client, $connection, $message) {
            echo "> [{$connection->getRemoteName()}] Received [{$message->getOpcode()}] {$message->getCloseStatus()}\n";
        })->onError(function ($client, $connection, $exception) {
            $name = $connection ? "[{$connection->getRemoteName()}]" : "[-]";
            echo "> {$name} Error: {$exception->getMessage()}\n";
        })->onTick(function ($client) use ($randStr) {
            if (!$client->isWritable()) {
                return;
            }
            // Random actions
            switch (rand(1, 5)) {
                case 1:
                    echo "< Sending text\n";
                    $client->text("Text message {$randStr()}");
                    break;
                case 2:
                    echo "< Sending binary\n";
                    $client->binary("Binary message {$randStr()}");
                    break;
                case 3:
                    echo "< Sending close\n";
                    $client->close(rand(1000, 2000), "Close message {$randStr(8)}");
                    break;
                case 4:
                    echo "< Sending ping\n";
                    $client->ping("Ping message {$randStr(8)}");
                    break;
                case 5:
                    echo "< Sending pong\n";
                    $client->pong("Pong message {$randStr(8)}");
                    break;
            }
        })->start();
    } catch (Throwable $e) {
        echo "> ERROR: {$e->getMessage()}\n";
    }
}
