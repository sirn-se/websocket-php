<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Frame;

use Phrity\Net\SocketStream;
use Psr\Log\LoggerAwareInterface;
use RuntimeException;
use Stringable;
use WebSocket\Exception\CloseException;
use WebSocket\Trait\{
    LoggerAwareTrait,
    OpcodeTrait,
    StringableTrait
};

/**
 * WebSocket\Frame\FrameHandler class.
 * Reads and writes Frames on stream.
 */
class FrameHandler implements LoggerAwareInterface, Stringable
{
    use LoggerAwareTrait;
    use OpcodeTrait;
    use StringableTrait;

    private SocketStream $stream;
    private bool $pushMasked;
    private bool $pullMaskedRequired;

    public function __construct(SocketStream $stream, bool $pushMasked, bool $pullMaskedRequired)
    {
        $this->stream = $stream;
        $this->pushMasked = $pushMasked;
        $this->pullMaskedRequired = $pullMaskedRequired;
        $this->initLogger();
    }

    // Pull frame from stream
    public function pull(): Frame
    {
        // Read the frame "header" first, two bytes.
        $data = $this->read(2);
        list ($byte_1, $byte_2) = array_values($this->unpack('C*', $data));
        $final = (bool)($byte_1 & 0b10000000); // Final fragment marker.
        $rsv1 = (bool)($byte_1 & 0b01000000);
        $rsv2 = (bool)($byte_1 & 0b00100000);
        $rsv3 = (bool)($byte_1 & 0b00010000);

        // Parse opcode
        $opcode_int = $byte_1 & 0b00001111;
        $opcode_ints = array_flip(self::$opcodes);
        $opcode = array_key_exists($opcode_int, $opcode_ints) ? $opcode_ints[$opcode_int] : strval($opcode_int);

        // Masking bit
        $masked = (bool)($byte_2 & 0b10000000);

        $payload = '';

        // Payload length
        $payload_length = $byte_2 & 0b01111111;

        if ($payload_length > 125) {
            if ($payload_length === 126) {
                $data = $this->read(2); // 126: Payload length is a 16-bit unsigned int
                $payload_length = current($this->unpack('n', $data));
            } else {
                $data = $this->read(8); // 127: Payload length is a 64-bit unsigned int
                $payload_length = current($this->unpack('J', $data));
            }
        }

        // Get masking key.
        if ($masked) {
            $masking_key = $this->stream->read(4);
        }

        // Get the actual payload, if any (might not be for e.g. close frames).
        if ($payload_length > 0) {
            $data = $this->read($payload_length);
            if ($masked) {
                // Unmask payload.
                for ($i = 0; $i < $payload_length; $i++) {
                    $payload .= ($data[$i] ^ $masking_key[$i % 4]);
                }
            } else {
                $payload = $data;
            }
        }

        $frame = new Frame($opcode, $payload, $final, $rsv1, $rsv2, $rsv3);
        $this->logger->debug("[frame-handler] Pulled '{$opcode}' frame", [
            'opcode' => $frame->getOpcode(),
            'final' => $frame->isFinal(),
            'content-length' => $frame->getPayloadLength(),
        ]);

        if ($this->pullMaskedRequired && !$masked) {
            $this->logger->error("[frame-handler] Masking required, but frame was unmasked");
            throw new CloseException(1002, 'Masking required');
        }

        return $frame;
    }

    // Push frame to stream
    public function push(Frame $frame): int
    {
        $payload = $frame->getPayload();
        $payload_length = $frame->getPayloadLength();

        $data = '';
        $byte_1 = $frame->isFinal() ? 0b10000000 : 0b00000000; // Final fragment marker.
        $byte_1 |= $frame->getRsv1() ? 0b01000000 : 0b00000000; // RSV1 bit.
        $byte_1 |= $frame->getRsv2() ? 0b00100000 : 0b00000000; // RSV2 bit.
        $byte_1 |= $frame->getRsv3() ? 0b00010000 : 0b00000000; // RSV3 bit.
        $byte_1 |= self::$opcodes[$frame->getOpcode()]; // Set opcode.
        $data .= pack('C', $byte_1);

        $byte_2 = $this->pushMasked ? 0b10000000 : 0b00000000; // Masking bit marker.

        // 7 bits of payload length
        if ($payload_length > 65535) {
            $data .= pack('C', $byte_2 | 0b01111111);
            $data .= pack('J', $payload_length);
        } elseif ($payload_length > 125) {
            $data .= pack('C', $byte_2 | 0b01111110);
            $data .= pack('n', $payload_length);
        } else {
            $data .= pack('C', $byte_2 | $payload_length);
        }

        // Handle masking.
        if ($this->pushMasked) {
            // Generate a random mask.
            $mask = '';
            for ($i = 0; $i < 4; $i++) {
                $mask .= chr(rand(0, 255));
            }
            $data .= $mask;

            // Append masked payload to frame.
            for ($i = 0; $i < $payload_length; $i++) {
                $data .= $payload[$i] ^ $mask[$i % 4];
            }
        } else {
            // Append payload as-is to frame.
            $data .= $payload;
        }

        // Write to stream.
        $written = $this->write($data);

        $this->logger->debug("[frame-handler] Pushed '{opcode}' frame", [
            'opcode' => $frame->getOpcode(),
            'final' => $frame->isFinal(),
            'content-length' => $frame->getPayloadLength(),
        ]);
        return $written;
    }

    // Secured read op
    private function read(int $length): string
    {
        $data = '';
        $read = 0;
        while ($read < $length) {
            $got = $this->stream->read($length - $read);
            if (empty($got)) {
                throw new RuntimeException('Empty read; connection dead?');
            }
            $data .= $got;
            $read = strlen($data);
        }
        return $data;
    }

    // Secured write op
    private function write(string $data): int
    {
        $length = strlen($data);
        $written = $this->stream->write($data);
        if ($written < $length) {
            throw new RuntimeException("Could only write {$written} out of {$length} bytes.");
        }
        return $written;
    }

    /** @return array<int> */
    private function unpack(string $format, string $string): array
    {
        /** @var array<int> $result */
        $result = unpack($format, $string);
        return $result;
    }
}
