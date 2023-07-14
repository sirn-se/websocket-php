<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Message;

use Psr\Log\{
    LoggerInterface,
    LoggerAwareInterface,
    NullLogger
};
use WebSocket\Frame\FrameHandler;

/**
 * WebSocket\Message\MessageHandler class.
 * Message/Frame handling.
 */
class MessageHandler implements LoggerAwareInterface
{
    private const DEFAULT_SIZE = 4096;

    private $frameHandler;
    private $factory;
    private $logger;
    private $readBuffer;

    public function __construct(FrameHandler $frameHandler)
    {
        $this->frameHandler = $frameHandler;
        $this->factory = new Factory();
        $this->setLogger(new NullLogger());
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
        $this->frameHandler->setLogger($logger);
    }

    // Push message
    public function push(Message $message, bool $masked, int $size = self::DEFAULT_SIZE): void
    {
        $frames = $message->getFrames($size);
        foreach ($frames as $frame) {
            $this->frameHandler->push($frame, $masked);
        }
        $this->logger->info("[message-handler] Pushed {$message}", [
            'opcode' => $message->getOpcode(),
            'content-length' => $message->getLength(),
            'frames' => count($frames),
        ]);
    }

    // Pull message
    public function pull(): Message
    {
        do {
            $frame = $this->frameHandler->pull();
            $final = $frame->isFinal();
            $continuation = $frame->isContinuation();
            $opcode = $frame->getOpcode();
            $payload = $frame->getPayload();

            // Continuation and factual opcode
            $payload_opcode = $continuation ? $this->readBuffer['opcode'] : $opcode;

            // First continuation frame, create buffer
            if (!$final && !$continuation) {
                $this->readBuffer = ['opcode' => $opcode, 'payload' => $payload, 'frames' => 1];
                continue; // Continue reading
            }

            // Subsequent continuation frames, add to buffer
            if ($continuation) {
                $this->readBuffer['payload'] .= $payload;
                $this->readBuffer['frames']++;
            }
        } while (!$final);

        // Final, return payload
        $frames = 1;
        if ($continuation) {
            $payload = $this->readBuffer['payload'];
            $frames = $this->readBuffer['frames'];
            $this->readBuffer = null;
        }

        // Create message instance
        $message = $this->factory->create($payload_opcode, $payload);
        $this->logger->info("[message-handler] Pulled {$message}", [
            'opcode' => $message->getOpcode(),
            'content-length' => $message->getLength(),
            'frames' => $frames,
        ]);

        return $message;
    }
}
