<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Test\DevTool;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\{
    AsCommand,
    Argument,
    Option,
};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;

#[AsCommand(
    name: 'frame:encode',
    description: 'Output frame encoded as base64 strings.'
)]
class FrameEncodeCommand
{
    public function __invoke(
        OutputInterface $output,
        #[Option('Opcode [integer 1-15]')]
        int $opcode = 1,
        #[Option('Payload')]
        string $payload = '',
        #[Option('Mask [4 character string, or true to generate]')]
        string|bool $mask = false,
        #[Option('Final')]
        bool $final = true,
        #[Option('Rsv1')]
        bool $rsv1 = false,
        #[Option('Rsv2')]
        bool $rsv2 = false,
        #[Option('Rsv3')]
        bool $rsv3 = false,
    ): int {
        if ($opcode < 1 || $opcode > 15) {
            throw new RuntimeException("Opcode must be integer in range 1-15, {$opcode} provided");
        }
        if (is_string($mask) && strlen($mask) !== 4) {
            throw new RuntimeException("Mask must be exactly 4 characters");
        }
        if ($mask === true) {
            $mask = '';
            for ($i = 0; $i < 4; $i++) {
                $mask .= chr(rand(0, 255));
            }
        }

        $payloadLength = strlen($payload);

        $header = '';
        $byte1 = $final ? 0b10000000 : 0b00000000; // Final fragment marker.
        $byte1 |= $rsv1 ? 0b01000000 : 0b00000000; // RSV1 bit.
        $byte1 |= $rsv2 ? 0b00100000 : 0b00000000; // RSV2 bit.
        $byte1 |= $rsv3 ? 0b00010000 : 0b00000000; // RSV3 bit.
        $byte1 |= $opcode; // Set opcode.
        $header .= pack('C', $byte1);

        $byte2 = is_string($mask) ? 0b10000000 : 0b00000000; // Masking bit marker.
        // 7 bits of payload length
        if ($payloadLength > 65535) {
            $header .= pack('C', $byte2 | 0b01111111);
            $header .= pack('J', $payloadLength);
        } elseif ($payloadLength > 125) {
            $header .= pack('C', $byte2 | 0b01111110);
            $header .= pack('n', $payloadLength);
        } else {
            $header .= pack('C', $byte2 | $payloadLength);
        }

        $output->writeln("Header:  " . base64_encode($header));

        if ($mask !== false) {
            $output->writeln("Mask:    " . base64_encode($mask));

            $payloadEncoded = '';
            // Append masked payload to frame.
            for ($i = 0; $i < $payloadLength; $i++) {
                $payloadEncoded .= $payload[$i] ^ $mask[$i % 4];
            }
        } else {
            $payloadEncoded = $payload;
        }

        $output->writeln("Payload: " . base64_encode($payloadEncoded));

        $all = $header . ($mask ?: '') . $payloadEncoded;
        $output->writeln("All:     " . base64_encode($all));

        return Command::SUCCESS;
    }
}
