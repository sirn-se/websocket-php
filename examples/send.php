<?php

/**
 * Simple send & receive client for test purpose.
 * Run in console: php examples/send.php <options> <message>
 *
 * Console options:
 *  --uri <uri> : The URI to connect to, default ws://localhost:8000
 *  --opcode <string> : Opcode to send, default 'text'
 *  --debug : Output log data (if logger is available)
 */

namespace WebSocket;

require __DIR__ . '/../vendor/autoload.php';

error_reporting(-1);

echo "> Send client\n";

// Server options specified or random
$options = array_merge([
    'filter'        => ['text'],
    'uri'           => 'ws://localhost:8000',
    'opcode'        => 'text',
    'return_obj'    => true,
], getopt('', ['uri:', 'opcode:', 'debug']));
$message = array_pop($argv);

// If debug mode and logger is available
if (isset($options['debug']) && class_exists('WebSocket\Test\EchoLog')) {
    $logger = new \WebSocket\Test\EchoLog();
    $options['logger'] = $logger;
    echo "> Using logger\n";
}

try {
    // Create client, send and recevie
    $client = new Client($options['uri'], $options);
    $type = $options['opcode'];
    $client->$type($message);
    echo "> Sent '{$message}' [opcode: {$options['opcode']}]\n";

    $message = $client->receive();
    if (!is_null($message)) {
        echo "> Got '{$message->getContent()}' [opcode: {$message->getOpcode()}]\n";
    }

    $client->close();
    echo "> Closing client\n";
    $message = $client->receive();
    if (!is_null($message)) {
        echo "> Got '{$message->getContent()}' [opcode: {$message->getOpcode()}]\n";
    }
} catch (\Throwable $e) {
    echo "> ERROR: {$e->getMessage()} [{$e->getCode()}]\n";
}
