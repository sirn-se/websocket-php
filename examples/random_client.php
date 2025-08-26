<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

/**
 * Websocket client that read/write random data.
 * Run in console: php examples/random_client.php
 *
 * Console options:
 *  --uri <uri> : The URI to connect to, default ws://localhost:8000
 *  --timeout <int|float> : Timeout in seconds, random default
 *  --framesize <int> : Frame payload size in bytes, random default
 *  --deflate : Add support for per-message deflate compression
 *  --debug : Output log data (if logger is available)
 */

namespace WebSocket;

use Phrity\Logger\Console\{
    ConsoleLogger,
    Verbosity,
};
use Throwable;
use WebSocket\Exception\ExceptionInterface;
use WebSocket\Message\{
    Binary,
    Close,
    Ping,
    Pong,
    Text,
};
use WebSocket\Middleware\{
    CloseHandler,
    CompressionExtension,
    PingResponder,
};
use WebSocket\Middleware\CompressionExtension\DeflateCompressor;

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

// Initiate client
// @phpstan-ignore while.alwaysTrue
while (true) {
    // Server options specified or random
    /**
     * @var array{
     *     uri: string,
     *     timeout: int<0, max>|float,
     *     framesize: int<1, max>,
     *     deflate: bool,
     * } $options
     */
    $options = array_merge([
        'uri'       => 'ws://localhost:80',
        'timeout'   => rand(1, 60),
        'framesize' => rand(1, 4096) * 8,
    ], getopt('', ['uri:', 'timeout:', 'framesize:', 'deflate']));

    try {
        $client = new Client($options['uri']);
        $client
            ->addMiddleware(new CloseHandler())
            ->addMiddleware(new PingResponder())
            ;

        if (isset($options['deflate'])) {
            $client->addMiddleware(new CompressionExtension(new DeflateCompressor()));
            echo "# Using per-message: deflate compression\n";
        }

        // If debug mode and logger is available
        if (class_exists(ConsoleLogger::class)) {
            $client->setLogger(new ConsoleLogger(format: '{level} | {message}', cliOptions: true));
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
        $client->onHandshake(function (Client $client, Connection $connection, $request, $response) {
            echo "> [{$connection->getRemoteName()}] Client connected {$response->getStatusCode()}\n";
        })->onDisconnect(function (Client $client, Connection $connection) {
            echo "> [{$connection->getRemoteName()}] Client disconnected\n";
        })->onText(function (Client $client, Connection $connection, Text $message) {
            echo "> [{$connection->getRemoteName()}] Received [{$message->getOpcode()}]\n";
        })->onBinary(function (Client $client, Connection $connection, Binary $message) {
            echo "> [{$connection->getRemoteName()}] Received [{$message->getOpcode()}]\n";
        })->onPing(function (Client $client, Connection $connection, Ping $message) {
            echo "> [{$connection->getRemoteName()}] Received [{$message->getOpcode()}]\n";
        })->onPong(function (Client $client, Connection $connection, Pong $message) {
            echo "> [{$connection->getRemoteName()}] Received [{$message->getOpcode()}]\n";
        })->onClose(function (Client $client, Connection $connection, Close $message) {
            echo "> [{$connection->getRemoteName()}] Received [{$message->getOpcode()}] {$message->getCloseStatus()}\n";
        })->onError(function (Client $client, Connection|null $connection, ExceptionInterface $exception) {
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
        $wait = rand(1, 10);
        echo "> ERROR: {$e->getMessage()}\n";
        echo ">        Wait {$wait} seconds for next attempt.\n";
        sleep($wait);
    }
}
