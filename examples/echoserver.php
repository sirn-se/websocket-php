<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

/**
 * Run in console: php examples/echoserver.php
 *
 * Console options:
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

echo "# Echo server! [phrity/websocket]\n";

// Server options specified or default
/**
 * @var array{
 *     port: int<1, 32768>,
 *     ssl: bool,
 *     timeout: int<0, max>|float,
 *     framesize: int<1, max>,
 *     connections: int<1, max>|null,
 *     deflate: bool,
 * } $options
 */
$options = array_merge([
    'port'  => 80,
], getopt('', ['port:', 'ssl', 'timeout:', 'framesize:', 'connections:', 'deflate']));

// Initiate server.
try {
    $server = new Server($options['port'], isset($options['ssl']));
    $server
        ->addMiddleware(new CloseHandler())
        ->addMiddleware(new PingResponder())
        ;

    // If debug mode and logger is available
    if (class_exists(ConsoleLogger::class)) {
        $server->setLogger(new ConsoleLogger(format: '{level} | {message}', cliOptions: true));
        echo "# Using logger\n";
    }
    if (isset($options['timeout'])) {
        $server->setTimeout($options['timeout']);
        echo "# Set timeout: {$options['timeout']}\n";
    }
    if (isset($options['framesize'])) {
        $server->setFrameSize($options['framesize']);
        echo "# Set frame size: {$options['framesize']}\n";
    }
    if (isset($options['connections'])) {
        $server->getConfiguration()->setMaxConnections($options['connections']);
        echo "# Set max connections: {$options['connections']}\n";
    }
    if (isset($options['deflate'])) {
        $server->addMiddleware(new CompressionExtension(new DeflateCompressor()));
        echo "# Using per-message: deflate compression\n";
    }

    echo "# Listening on port {$server->getPort()}\n";
    $server->onHandshake(function (Server $server, Connection $connection, $request, $response) {
        echo "> [{$connection->getRemoteName()}] Client connected {$request->getUri()}\n";
    })->onDisconnect(function (Server $server, Connection $connection) {
        echo "> [{$connection->getRemoteName()}] Client disconnected\n";
    })->onText(function (Server $server, Connection $connection, Text $message) {
        echo "> [{$connection->getRemoteName()}] Received [{$message->getOpcode()}] {$message->getContent()}\n";
        switch ($message->getContent()) {
            // Connection commands
            case '@close':
                echo "< [{$connection->getRemoteName()}] Sending Close\n";
                $connection->send(new Close());
                break;
            case '@ping':
                echo "< [{$connection->getRemoteName()}] Sending Ping\n";
                $connection->send(new Ping());
                break;
            case '@disconnect':
                echo "< [{$connection->getRemoteName()}] Disconnecting\n";
                $connection->disconnect();
                break;
            case '@info':
                $msg = "Connection info:\n";
                $msg .= "  - Local:       {$connection->getName()}\n";
                $msg .= "  - Remote:      {$connection->getRemoteName()}\n";
                $msg .= "  - Request:     {$connection->getHandshakeRequest()?->getUri()}\n";
                $msg .= "  - Response:    {$connection->getHandshakeResponse()?->getStatusCode()}\n";
                $msg .= "  - Connected:   " . json_encode($connection->isConnected()) . "\n";
                $msg .= "  - Readable:    " . json_encode($connection->isReadable()) . "\n";
                $msg .= "  - Writable:    " . json_encode($connection->isWritable()) . "\n";
                $msg .= "  - Timeout:     {$connection->getConfiguration()->getTimeout()}s\n";
                $msg .= "  - Frame size:  {$connection->getConfiguration()->getFrameSize()}b\n";
                echo "< [{$connection->getRemoteName()}] {$msg}";
                $server->send(new Text($msg));
                break;

            // Server commands
            case '@server-stop':
                echo "< [{$connection->getRemoteName()}] Stop server\n";
                $server->stop();
                break;
            case '@server-shutdown':
                echo "< [{$connection->getRemoteName()}] Shutdown server\n";
                $server->shutdown();
                break;
            case '@server-close':
                echo "< [{$connection->getRemoteName()}] Broadcast Close\n";
                $server->send(new Close());
                break;
            case '@server-ping':
                echo "< [{$connection->getRemoteName()}] Broadcast Ping\n";
                $server->send(new Ping());
                break;
            case '@server-disconnect':
                echo "< [{$connection->getRemoteName()}] Disconnecting server\n";
                $server->disconnect();
                break;
            case '@server-info':
                $msg = "Server info:\n";
                $msg .= "  - Running:     " . json_encode($server->isRunning()) . "\n";
                $msg .= "  - Connections: {$server->getConnectionCount()}\n";
                $msg .= "  - Port:        {$server->getPort()}\n";
                $msg .= "  - Scheme:      {$server->getScheme()}\n";
                $msg .= "  - Timeout:     {$server->getTimeout()}s\n";
                $msg .= "  - Frame size:  {$server->getFrameSize()}b\n";
                echo "< [{$connection->getRemoteName()}] {$msg}";
                $server->send(new Text($msg));
                break;

            // Echo received message
            default:
                $connection->send($message); // Echo
                echo "< [{$connection->getRemoteName()}] Sent [{$message->getOpcode()}] {$message->getContent()}\n";
        }
    })->onBinary(function (Server $server, Connection $connection, Binary $message) {
        echo "> [{$connection->getRemoteName()}] Received [{$message->getOpcode()}]\n";
        $connection->send($message); // Echo
        echo "< [{$connection->getRemoteName()}] Sent [{$message->getOpcode()}] {$message->getContent()}\n";
    })->onPing(function (Server $server, Connection $connection, Ping $message) {
        echo "> [{$connection->getRemoteName()}] Received [{$message->getOpcode()}] {$message->getContent()}\n";
    })->onPong(function (Server $server, Connection $connection, Pong $message) {
        echo "> [{$connection->getRemoteName()}] Received [{$message->getOpcode()}] {$message->getContent()}\n";
    })->onClose(function (Server $server, Connection $connection, Close $message) {
        echo "> [{$connection->getRemoteName()}] Received [{$message->getOpcode()}] "
            . "{$message->getCloseStatus()} {$message->getContent()}\n";
    })->onError(function (Server $server, Connection|null $connection, ExceptionInterface $exception) {
        echo "> Error: {$exception->getMessage()}\n";
    })->start();
} catch (Throwable $e) {
    echo "# ERROR: {$e->getMessage()}\n";
}
