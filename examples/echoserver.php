<?php

/**
 * This file is used for the tests, but can also serve as an example of a WebSocket\Server.
 * Run in console: php examples/echoserver.php
 *
 * Console options:
 *  --port <int> : The port to listen to, default 8000
 *  --timeout <int> : Timeout in seconds, default 200 seconds
 *  --debug : Output log data (if logger is available)
 */

namespace WebSocket;

require __DIR__ . '/../vendor/autoload.php';

error_reporting(-1);

echo "> Echo server\n";

// Server options specified or random
$options = array_merge([
    'port'          => 8000,
    'timeout'       => 200,
    'filter'        => ['text', 'binary', 'ping', 'pong', 'close'],
    'return_obj'    => true,
], getopt('', ['port:', 'timeout:', 'debug']));

// If debug mode and logger is available
if (isset($options['debug']) && class_exists('WebSocket\Test\EchoLog')) {
    $logger = new \WebSocket\Test\EchoLog();
    $options['logger'] = $logger;
    echo "> Using logger\n";
}

// Initiate server.
try {
    $server = new Server($options);
} catch (ConnectionException $e) {
    echo "> ERROR: {$e->getMessage()}\n";
    die();
}

echo "> Listening to port {$server->getPort()}\n";

// Force quit to close server
while (true) {
    try {
        while ($server->accept()) {
            echo "> Accepted on port {$server->getPort()}\n";
            while (true) {
                $message = $server->receive();
                if (is_null($message)) {
                    echo "> Closing connection\n";
                    continue 2;
                }
                $opcode = $message->getOpcode();
                echo "> Got '{$message->getContent()}' [opcode: {$opcode}]\n";
                if (!in_array($opcode, ['text', 'binary'])) {
                    continue;
                }
                // Allow certain string to trigger server action
                switch ($message->getContent()) {
                    case 'exit':
                        echo "> Client told me to quit.  Bye bye.\n";
                        $server->close();
                        echo "> Close status: {$server->getCloseStatus()}\n";
                        exit;
                    case 'headers':
                        $headers = '';
                        foreach ($server->getHandshakeRequest()->getHeaders() as $key => $lines) {
                            foreach ($lines as $line) {
                                $headers .= "{$key}: {$line}\r\n";
                            }
                        }
                        $server->text($headers);
                        break;
                    case 'ping':
                        $server->ping($message->getContent());
                        break;
                    case 'auth':
                        $auth = $server->getHandshakeRequest()->getHeaderLine('Authorization');
                        $server->text("{$auth} - {$message->getContent()}");
                        break;
                    default:
                        $server->text($message->getContent());
                }
            }
        }
    } catch (\Throwable $e) {
        echo "> ERROR: {$e->getMessage()} [{$e->getCode()}]\n";
    }
}
