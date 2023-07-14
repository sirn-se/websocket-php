<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Http;

use Phrity\Net\{
    SocketStream,
    Uri
};
use Psr\Http\Message\{
    MessageInterface,
    RequestInterface,
    ResponseInterface,
    StreamInterface
};
use Psr\Log\{
    LoggerInterface,
    LoggerAwareInterface,
    NullLogger
};
use RuntimeException;

/**
 * WebSocket\Http\HttpHandler class.
 * Reads and writes HTTP message to/from stream.
 */
class HttpHandler implements LoggerAwareInterface
{
    private $stream;
    private $logger;

    public function __construct(SocketStream $stream)
    {
        $this->stream = $stream;
        $this->setLogger(new NullLogger());
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function pull(): MessageInterface
    {
        $data = '';
        do {
            $buffer = $this->stream->readLine(1024);
            $data .= $buffer;
        } while (substr_count($data, "\r\n\r\n") == 0);

        list ($head, $body) = explode("\r\n\r\n", $data);
        $headers = array_filter(explode("\r\n", $head));
        $status = array_shift($headers);

        // Pulling server request
        preg_match('!^(?P<method>[A-Z]+) (?P<path>[^ ]*) HTTP/(?P<version>[0-9/.]+)!', $status, $matches);
        if (!empty($matches)) {
            $message = new ServerRequest($matches['method']);
            $path = $matches['path'];
            $version = $matches['version'];
        }

        // Pulling response
        preg_match('!^HTTP/(?P<version>[0-9/.]+) (?P<code>[0-9]*) (?P<reason>.*)!', $status, $matches);
        if (!empty($matches)) {
            $message = new Response($matches['code'], $matches['reason']);
            $version = $matches['version'];
        }

        if (empty($message)) {
            throw new RuntimeException('Invalid Http request.');
        }

        $message = $message->withProtocolVersion($version);
        foreach ($headers as $header) {
            $parts = explode(':', $header, 2);
            if (count($parts) == 2) {
                $message = $message->withHeader($parts[0], $parts[1]);
            }
        }
        if ($message instanceof Request) {
            $uri = new Uri("//{$message->getHeaderLine('host')}{$path}");
            $message = $message->withUri($uri);
        }

        return $message;
    }

    public function push(MessageInterface $message): int
    {
        $data = implode("\r\n", $message->getAsArray()) . "\r\n\r\n";
        return $this->stream->write($data);
    }
}
