<?php

/**
 * Copyright (C) 2014-2023 Textalk/Abicart and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://github.com/sirn-se/websocket-php/blob/master/COPYING.md
 */

namespace WebSocket;

use Phrity\Net\SocketStream;
use Phrity\Net\StreamException;
use Psr\Log\{
    LoggerAwareInterface,
    LoggerAwareTrait,
    LoggerInterface,
    NullLogger
};
use RuntimeException;
use WebSocket\Message\{
    Factory,
    Message
};
use Throwable;

class Connection implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use OpcodeTrait;

    private $stream;
    private $read_buffer;
    private $msg_factory;
    private $frame_handler;
    private $options = [];

    protected $is_closing = false;
    protected $close_status = null;


    /* ---------- Construct & Destruct ----------------------------------------------- */

    public function __construct(SocketStream $stream, array $options = [])
    {
        $this->stream = $stream;
        $this->setOptions($options);
        $this->setLogger(new NullLogger());
        $this->msg_factory = new Factory();
        $this->frame_handler = new \WebSocket\Frame\FrameHandler($this->stream);
    }

    public function __destruct()
    {
        if ($this->isConnected()) {
            $this->stream->close();
        }
    }

    public function setOptions(array $options = []): void
    {
        $this->options = array_merge($this->options, $options);
    }

    public function getCloseStatus(): ?int
    {
        return $this->close_status;
    }

    /**
     * Tell the socket to close.
     *
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
        $message = $this->msg_factory->create('close', $status_str . $message);
        $this->pushMessage($message);

        $this->logger->debug("[connection] Closing with status: {$status}.");

        $this->is_closing = true;
        while (true) {
            $message = $this->pullMessage();
            if ($message->getOpcode() == 'close') {
                break;
            }
        }
    }


    /* ---------- Message methods ---------------------------------------------------- */

    // Push a message to stream
    public function pushMessage(Message $message, ?bool $masked = null): void
    {
        try {
            $masked = is_null($masked) ? $this->options['masked'] : $masked;
            $frames = $message->getFrames($this->options['fragment_size']);
            foreach ($frames as $frame) {
                $this->frame_handler->push($frame, $masked);
                $this->logger->debug("[connection] Pushed '{opcode}' frame", [
                    'opcode' => $frame->getOpcode(),
                    'final' => $frame->isFinal(),
                    'content-length' => $frame->getPayloadLength(),
                ]);
            }
            $this->logger->info("[connection] Pushed {$message}", [
                'opcode' => $message->getOpcode(),
                'content-length' => $message->getLength(),
                'frames' => count($frames),
            ]);
        } catch (Throwable $e) {
            $this->throwException($e);
        }
    }

    // Pull a message from stream
    public function pullMessage(): ?Message
    {
        try {
            do {
                $frame = $this->frame_handler->pull();
                if (empty($frame)) {
                    return null;
                }

                $final = $frame->isFinal();
                $continuation = $frame->isContinuation();
                $opcode = $frame->getOpcode();
                $payload = $frame->getPayload();

                $this->logger->debug("[connection] Pulled '{opcode}' frame", [
                    'opcode' => $opcode,
                    'final' => $final,
                    'content-length' => $frame->getPayloadLength(),
                ]);

                // Continuation and factual opcode
                $payload_opcode = $continuation ? $this->read_buffer['opcode'] : $opcode;

                // First continuation frame, create buffer
                if (!$final && !$continuation) {
                    $this->read_buffer = ['opcode' => $opcode, 'payload' => $payload, 'frames' => 1];
                    continue; // Continue reading
                }

                // Subsequent continuation frames, add to buffer
                if ($continuation) {
                    $this->read_buffer['payload'] .= $payload;
                    $this->read_buffer['frames']++;
                }
            } while (!$final);

            // Final, return payload
            $frames = 1;
            if ($continuation) {
                $payload = $this->read_buffer['payload'];
                $frames = $this->read_buffer['frames'];
                $this->read_buffer = null;
            }

            $factory = new Factory();
            $message = $factory->create($payload_opcode, $payload);

            $this->autoRespond($message);

            $this->logger->info("[connection] Pulled {$message}", [
                'opcode' => $message->getOpcode(),
                'content-length' => $message->getLength(),
                'frames' => $frames,
            ]);

            return $message;
        } catch (Throwable $e) {
            $this->throwException($e);
        }
    }


    /* ---------- Frame I/O methods -------------------------------------------------- */

    // Trigger auto response for frame
    private function autoRespond(Message $message)
    {
        switch ($message->getOpcode()) {
            case 'ping':
                // If we received a ping, respond with a pong
                $this->logger->debug("[connection] Received 'ping', sending 'pong'.");
                $pong = $this->msg_factory->create('pong', $message->getContent());
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
                    $message = $this->msg_factory->create('close', $ack);
                    $this->pushMessage($message);
                } else {
                    $this->is_closing = false; // A close response, all done.
                }
                $this->disconnect();
                return;
        }
    }


    /* ---------- Stream I/O methods ------------------------------------------------- */

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
     * If connected to stream.
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->stream->isConnected();
    }

    /**
     * Return type of connection.
     * @return string|null Type of connection or null if invalid type.
     */
    public function getType(): ?string
    {
        return $this->stream->getResourceType();
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

    /**
     * Get meta data for connection.
     * @return array
     */
    public function getMeta(): array
    {
        return $this->stream->getMetadata() ?: [];
    }

    /**
     * Returns current position of stream pointer.
     * @return int
     * @throws ConnectionException
     */
    public function tell(): int
    {
        return $this->stream->tell();
    }

    /**
     * If stream pointer is at end of file.
     * @return bool
     */
    public function eof(): int
    {
        return $this->stream->eof();
    }


    public function pushHttp(\WebSocket\Http\Message $message): void
    {
        $this->stream->write($message->render());
    }

    public function pullHttp(\WebSocket\Http\Message $message): \WebSocket\Http\Message
    {
        $response = '';
        do {
            $buffer = $this->stream->readLine(1024);
            $response .= $buffer;
        } while (substr_count($response, "\r\n\r\n") == 0);
        return $message->parse($response);
    }


    /* ---------- Stream option methods ---------------------------------------------- */

    /**
     * Set time out on connection.
     * @param int $seconds Timeout part in seconds
     * @param int $microseconds Timeout part in microseconds
     * @return bool
     */
    public function setTimeout(int $seconds, int $microseconds = 0): bool
    {
        $this->logger->debug("[connection] Setting timeout {$seconds}.{$microseconds} seconds");
        return $this->stream->setTimeout($seconds, $microseconds);
    }


    /* ---------- Stream read/write methods ------------------------------------------ */

    /**
     * Read line from stream.
     * @param int $length Maximum number of bytes to read
     * @param string $ending Line delimiter
     * @return string Read data
     */
    public function getLine(int $length, string $ending): string
    {
        $this->deprecated('getLine() on Connection is deprecated.');
        $line = stream_get_line($this->stream, $length, $ending);
        if ($line === false) {
            $this->throwException(null, 'Could not read from stream');
        }
        $read = strlen($line);
        $this->logger->debug("[connection] Read {$read} bytes of line.");
        return $line;
    }

    /**
     * Read line from stream.
     * @param int $length Maximum number of bytes to read
     * @return string Read data
     */
    public function readLine(int $length): string
    {
        $this->deprecated('readLine() on Connection is deprecated.');
        $line = $this->stream->readLine($length);
        $read = strlen($line);
        $this->logger->debug("[connection] Read {$read} bytes of line.");
        return $line;
    }

    /**
     * Read characters from stream.
     * @param int $length Maximum number of bytes to read
     * @return string Read data
     * @deprecated Will be removed in 2.0
     */
    public function read(string $length): string
    {
        $this->deprecated('read() on Connection is deprecated.');
        try {
            $data = '';
            while (strlen($data) < $length) {
                $buffer = $this->stream->read($length - strlen($data));
                if ($buffer === '') {
                    $this->throwException(null, "Empty read; connection dead?");
                }
                $data .= $buffer;
                $read = strlen($data);
                $this->logger->debug("[connection] Read {$read} of {$length} bytes.");
            }
            return $data;
        } catch (RuntimeException $e) {
            $this->throwException($e);
        }
    }

    /**
     * Write characters to stream.
     * @param string $data Data to read
     * @deprecated Will be removed in 2.0
     */
    public function write(string $data): void
    {
        $this->deprecated('write() on Connection is deprecated.');
        try {
            $length = strlen($data);
            $written = $this->stream->write($data);
            if ($written < strlen($data)) {
                $this->throwException(null, "Could only write {$written} out of {$length} bytes.");
            }
            $this->logger->debug("[connection] Wrote {$written} of {$length} bytes.");
        } catch (RuntimeException $e) {
            $this->throwException($e);
        }
    }


    /* ---------- Internal helper methods -------------------------------------------- */

    private function throwException(?\Throwable $e = null, ?string $message = null, int $code = 0): void
    {
        $meta = ['closed' => true];

        if ($e instanceof ConnectionException) {
            $message = $message ?: $e->getMessage();
            $code = $code ?: $e->getCode();
        } elseif ($e instanceof BadOpcodeException) {
            throw $e;
        }
        if ($this->isConnected()) {
            $meta = $this->getMeta();
            $this->disconnect();
            if (!empty($meta['timed_out'])) {
                $message = $message ?: 'Connection timeout';
                $this->logger->error("[connection] {$message}", $meta);
                throw new TimeoutException($message, ConnectionException::TIMED_OUT, $meta);
            }
            if (!empty($meta['eof'])) {
                $message = $message ?: 'Connection closed';
                $code = ConnectionException::EOF;
            }
        }

        $message = $message ?: 'Unspecified error';
        $this->logger->error("[connection] {$message}", $meta);
        throw new ConnectionException($message, $code, $meta);
    }

    private function deprecated(string $message): void
    {
        $this->logger->debug("[connection] {$message}", $meta);
        trigger_error($message, E_USER_DEPRECATED);
    }
}
