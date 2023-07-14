<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket;

use Phrity\Net\SocketStream;
use Psr\Log\{
    LoggerAwareInterface,
    LoggerInterface,
    NullLogger
};
use RuntimeException;
use Throwable;
use WebSocket\Exception;
use WebSocket\Frame\FrameHandler;
use WebSocket\Http\{
    HttpHandler,
    Message as HttpMessage
};
use WebSocket\Message\{
    Factory,
    Message,
    MessageHandler
};

/**
 * WebSocket\Connection class.
 * A client/server connection.
 */
class Connection implements LoggerAwareInterface
{
    use OpcodeTrait;

    private $stream;
    private $httpHandler;
    private $messageFactory;
    private $messageHandler;
    private $options = ['masked' => false, 'fragment_size' => 4096];
    private $logger;

    protected $is_closing = false;
    protected $close_status = null;


    /* ---------- Construct & Destruct ----------------------------------------------------------------------------- */

    public function __construct(SocketStream $stream, array $options = [])
    {
        $this->stream = $stream;
        $this->messageFactory = new Factory();
        $this->httpHandler = new HttpHandler($this->stream);
        $this->messageHandler = new MessageHandler(new FrameHandler($this->stream));
        $this->setLogger(new NullLogger());
        $this->setOptions($options);
    }

    public function __destruct()
    {
        if ($this->isConnected()) {
            $this->stream->close();
        }
    }


    /* ---------- Configuration ------------------------------------------------------------------------------------ */

    /**
     * Set logger.
     * @param Psr\Log\LoggerInterface $logger Logger implementation
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
        $this->httpHandler->setLogger($logger);
        $this->messageHandler->setLogger($logger);
    }

    /**
     * Set connection options.
     * @param array $options Options
     */
    public function setOptions(array $options = []): void
    {
        $this->options = array_merge($this->options, $options);
        if (!empty($options['logger'])) {
            $this->setLogger($options['logger']);
        }
        if (!empty($options['timeout'])) {
            $this->setTimeout($options['timeout']);
        }
    }

    /**
     * Set time out on connection.
     * @param int $seconds Timeout part in seconds
     * @param int $microseconds Timeout part in microseconds
     */
    public function setTimeout(int $seconds, int $microseconds = 0): void
    {
        $this->stream->setTimeout($seconds, $microseconds);
        $this->logger->debug("[connection] Setting timeout {$seconds}.{$microseconds} seconds");
    }


    /* ---------- Connection management ---------------------------------------------------------------------------- */

    /**
     * If connected to stream.
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->stream->isConnected();
    }

    /**
     * Close connection stream.
     * @return bool
     */
    public function disconnect(): bool
    {
        $this->logger->info('[connection] Closing connection');
        $this->stream->close();
        return true;
    }

    /**
     * Tell the socket to close.
     * @param integer $status  http://tools.ietf.org/html/rfc6455#section-7.4
     * @param string  $message A closing message, max 125 bytes.
     */
    public function close(int $status = 1000, string $message = 'ttfn'): void
    {
        $status_binstr = sprintf('%016b', $status);
        $status_str = '';
        foreach (str_split($status_binstr, 8) as $binstr) {
            $status_str .= chr(bindec($binstr));
        }
        $message = $this->messageFactory->create('close', $status_str . $message);
        $this->pushMessage($message);

        $this->logger->debug("[connection] Closing with status: {$status}.");

        $this->is_closing = true;
        while (true) {
            $message = $this->pullMessage();
            if ($message->getOpcode() == 'close') {
                return;
            }
        }
    }


    /* ---------- Connection state --------------------------------------------------------------------------------- */

    /**
     * Get connection close status.
     * @return int|null Current close status
     */
    public function getCloseStatus(): ?int
    {
        return $this->close_status;
    }

    /**
     * Get name of local socket, or null if not connected.
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->stream->getLocalName();
    }

    /**
     * Get name of remote socket, or null if not connected.
     * @return string|null
     */
    public function getRemoteName(): ?string
    {
        return $this->stream->getRemoteName();
    }


    /* ---------- WebSocket Message methods ------------------------------------------------------------------------ */

    // Push a message to stream
    public function pushMessage(Message $message, ?bool $masked = null): void
    {
        try {
            $masked = is_null($masked) ? $this->options['masked'] : $masked;
            $this->messageHandler->push($message, $masked, $this->options['fragment_size']);
        } catch (Throwable $e) {
            $this->throwException($e);
        }
    }

    // Pull a message from stream
    public function pullMessage(): Message
    {
        try {
            $message = $this->messageHandler->pull();
            $this->autoRespond($message);
            return $message;
        } catch (Throwable $e) {
            $this->throwException($e);
        }
    }


    /* ---------- HTTP Message methods ----------------------------------------------------------------------------- */

    public function pushHttp(HttpMessage $message): void
    {
        $this->httpHandler->push($message);
    }

    public function pullHttp(): HttpMessage
    {
        return $this->httpHandler->pull();
    }


    /* ---------- Internal helper methods -------------------------------------------------------------------------- */

    protected function throwException(Throwable $e): void
    {
        // Internal exceptions are handled and re-thrown
        if ($e instanceof Exception) {
            $this->logger->error("[connection] {$e->getMessage()} ({$e->getCode()})");
            $this->disconnect();
            throw $e;
        }
        // External exceptions are converted to internal
        $exception = get_class($e);
        if ($this->isConnected()) {
            $meta = $this->stream->getMetadata();
            if (!empty($meta['timed_out'])) {
                $message = "Connection timeout: {$e->getMessage()}";
                $this->logger->error("[connection] {$e->getMessage()} ({$e->getCode()}) original: {$exception}");
                $this->disconnect();
                throw new TimeoutException($message, Exception::TIMED_OUT, $meta);
            }
            if (!empty($meta['eof'])) {
                $message = "Connection closed: {$e->getMessage()}";
                $this->logger->error("[connection] {$e->getMessage()} ({$e->getCode()}) original: {$exception}");
                $this->disconnect();
                throw new ConnectionException($message, Exception::EOF, $meta);
            }
        }
        $this->disconnect();
        $message = "Connection error: {$e->getMessage()}";
        $this->logger->error("[connection] {$message}  original: {$exception}");
        throw new ConnectionException($message, 0);
    }

    // Trigger auto response for frame
    protected function autoRespond(Message $message): void
    {
        switch ($message->getOpcode()) {
            case 'ping':
                // If we received a ping, respond with a pong
                $this->logger->debug("[connection] Received 'ping', sending 'pong'.");
                $pong = $this->messageFactory->create('pong', $message->getContent());
                $this->pushMessage($pong);
                return;
            case 'close':
                // If we received close, possibly acknowledge and close connection
                $status_bin = '';
                $status = '';
                $payload = $message->getContent();
                if ($message->getLength() > 0) {
                    $status_bin = $payload[0] . $payload[1];
                    $status = current(unpack('n', $payload));
                    $this->close_status = $status;
                }
                // Get additional close message
                $message->setContent($message->getLength() >= 2 ? substr($payload, 2) : '');

                $this->logger->debug("[connection] Received 'close', status: {$status}.");
                if (!$this->is_closing) {
                    $ack =  "{$status_bin}Close acknowledged: {$status}";
                    $message = $this->messageFactory->create('close', $ack);
                    $this->pushMessage($message);
                } else {
                    $this->is_closing = false; // A close response, all done.
                }
                $this->disconnect();
                return;
        }
    }
}
