<?php

namespace WebSocket\Frame;

use Closure;
use Phrity\Net\SocketStream;
use WebSocket\ConnectionException;
use WebSocket\OpcodeTrait;

/**
 * WebSocket\Frame\FrameHandler class.
 * Reads and writes Frames on stream.
 */
class FrameHandler
{
    use OpcodeTrait;

    private $stream;

    public function __construct(SocketStream $stream)
    {
        $this->stream = $stream;
    }

    // Pull frame from stream
    public function pull(): ?Frame
    {
        // Read the frame "header" first, two bytes.
        $data = $this->stream->read(2);
        if (empty($data)) {
            throw new ConnectionException('Empty read; connection dead?');
        }

        list ($byte_1, $byte_2) = array_values(unpack('C*', $data));
        $final = (bool)($byte_1 & 0b10000000); // Final fragment marker.
        $rsv = $byte_1 & 0b01110000; // Unused bits, ignore

        // Parse opcode
        $opcode_int = $byte_1 & 0b00001111;
        $opcode_ints = array_flip(self::$opcodes);
        if (!array_key_exists($opcode_int, $opcode_ints)) {
            $warning = "Bad opcode in websocket frame: {$opcode_int}";
            throw new ConnectionException($warning, ConnectionException::BAD_OPCODE);
        }
        $opcode = $opcode_ints[$opcode_int];

        // Masking bit
        $masked = (bool)($byte_2 & 0b10000000);

        $payload = '';

        // Payload length
        $payload_length = $byte_2 & 0b01111111;

        if ($payload_length > 125) {
            if ($payload_length === 126) {
                $data = $this->stream->read(2); // 126: Payload is a 16-bit unsigned int
                $payload_length = current(unpack('n', $data));
            } else {
                $data = $this->stream->read(8); // 127: Payload is a 64-bit unsigned int
                $payload_length = current(unpack('J', $data));
            }
        }

        // Get masking key.
        if ($masked) {
            $masking_key = $this->stream->read(4);
        }

        // Get the actual payload, if any (might not be for e.g. close frames).
        if ($payload_length > 0) {
            $data = '';
            $read = 0;
            while ($read < $payload_length) {
                $data .= $this->stream->read($payload_length - $read);
                $read = strlen($data);
            }
            if ($masked) {
                // Unmask payload.
                for ($i = 0; $i < $payload_length; $i++) {
                    $payload .= ($data[$i] ^ $masking_key[$i % 4]);
                }
            } else {
                $payload = $data;
            }
        }
        return new Frame($opcode, $payload, $final);
    }

    // Push frame to stream
    public function push(Frame $frame, bool $masked): int
    {
        $final = $frame->isFinal();
        $payload = $frame->getPayload();
        $opcode = $frame->getOpcode();
        $payload_length = $frame->getPayloadLength();

        $data = '';
        $byte_1 = $final ? 0b10000000 : 0b00000000; // Final fragment marker.
        $byte_1 |= self::$opcodes[$opcode]; // Set opcode.
        $data .= pack('C', $byte_1);

        $byte_2 = $masked ? 0b10000000 : 0b00000000; // Masking bit marker.

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
        if ($masked) {
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
        $length = strlen($data);
        $written = $this->stream->write($data);
        if ($written < $length) {
            throw new ConnectionException("Could only write {$written} out of {$length} bytes.");
        }
        return $written;
    }
}
