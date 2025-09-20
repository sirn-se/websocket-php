<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

/**
 * Run in console: php examples/delegating-server.php
 * Server will forward text and binary messages to remote server.
 *
 * Console options:
 *  --remote <string> : Upstream server to delegate, required
 *  --port <int> : The port to listen to, default 80
 *  --ssl : Use SSL, default false
 *  --timeout <int|float> : Timeout in seconds
 *  --framesize <int> : Frame payload size in bytes
 *  --connections <int> : Max number of connections, default unlimited
 *  --deflate : Add support for per-message deflate compression
 *  --debug : Output log data (if logger is available)
 */

namespace WebSocket;

use Phrity\Logger\Console\{
    ConsoleLogger,
    Verbosity,
};
use Throwable;
use WebSocket\Message\{
    Close,
    Ping,
    Text,
};
use WebSocket\Middleware\{
    CloseHandler,
    CompressionExtension,
    PingInterval,
    PingResponder,
};
use WebSocket\Middleware\CompressionExtension\DeflateCompressor;

require __DIR__ . '/../vendor/autoload.php';

error_reporting(-1);

echo "# Delegating server! [phrity/websocket]\n";

// Server options specified or default
/**
 * @var array{
 *     remote: string,
 *     port: int<1, 32768>,
 *     ssl: bool,
 *     timeout: int<0, max>|float,
 *     framesize: int<1, max>,
 *     connections: int<1, max>|null,
 *     deflate: bool,
 * } $options
 */
$options = array_merge([
    'remote'    => null,
    'port'      => 80,
    'timeout'   => 1,
], getopt('', ['remote:', 'port:', 'ssl', 'timeout:', 'framesize:', 'connections:', 'deflate']));

if (is_null($options['remote'])) {
    die("Remote URI must be provided: php delegating-server.php --remote=ws://example-server.com\n");
}

try {
    // Initiate server
    echo "# Setting up Server\n";
    $server = new Server($options['port'], isset($options['ssl']));
    $server
        ->addMiddleware(new CloseHandler())
        ->addMiddleware(new PingResponder())
        ->setTimeout($options['timeout'])
        ;

    // Initiate client
    echo "# Setting up Client\n";
    $client = new Client($options['remote']);
    $client
        ->addMiddleware(new CloseHandler())
        ->addMiddleware(new PingResponder())
        ->addMiddleware(new PingInterval(30))
        ->setTimeout($options['timeout'])
        ;

    // Configuration
    echo "# Set timeout: {$options['timeout']}\n";
    if (class_exists(ConsoleLogger::class)) {
        $logger = new ConsoleLogger(format: '{level} | {message}', cliOptions: true);
        $server->setLogger($logger);
        $client->setLogger($logger);
        echo "# Using logger\n";
    }
    if (isset($options['framesize'])) {
        $server->setFrameSize($options['framesize']);
        $client->setFrameSize($options['framesize']);
        echo "# Set frame size: {$options['framesize']}\n";
    }
    if (isset($options['connections'])) {
        $server->getConfiguration()->setMaxConnections($options['connections']);
        echo "# Set max connections: {$options['connections']}\n";
    }
    if (isset($options['deflate'])) {
        $server->addMiddleware(new CompressionExtension(new DeflateCompressor()));
        $client->addMiddleware(new CompressionExtension(new DeflateCompressor()));
        echo "# Using per-message: deflate compression\n";
    }

    // Bind
    $server
        ->onText(function ($server, $connection, $message) use ($client) {
            // Forward received message to remote server
            echo " -> Forward {$message} to remote server \n";
            $client->send($message);
        })
        ->onBinary(function ($server, $connection, $message) use ($client) {
            // Forward received message to remote server
            echo " -> Forward {$message} to remote server \n";
            $client->send($message);
        })
        ->onTick(function ($server) {
            // We need to stop the listener to give client a chance to run
            $server->stop();
        });
    $client
        ->onText(function ($client, $connection, $message) use ($server) {
            // Broadcast received message to all connected clients
            echo " <- Delegating {$message} to client(s) \n";
            $server->send($message);
        })
        ->onBinary(function ($client, $connection, $message) use ($server) {
            // Broadcast received message to all connected clients
            echo " <- Delegating {$message} to client(s) \n";
            $server->send($message);
        })
        ->onClose(function ($client, $connection, $message) use ($server) {
            // Broadcast received message to all connected clients
            echo " <- Delegating {$message} to client(s) \n";
            $server->send($message);
        })
        ->onTick(function ($client) {
            // We need to stop the listener to give server a chance to run
            $client->stop();
        });

    // Run loop
    // @phpstan-ignore while.alwaysTrue
    while (true) {
        $client->start();
        $server->start();
    }
} catch (Throwable $e) {
    echo "# ERROR: {$e->getMessage()}\n";
}
