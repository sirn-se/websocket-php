<?php

/**
 * Copyright (C) 2014-2024 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Message;

use Psr\Log\{
    LoggerAwareInterface,
    LoggerInterface,
    NullLogger
};
use Stringable;
use WebSocket\Exception\BadOpcodeException;
use WebSocket\Frame\FrameHandler;
use WebSocket\Trait\StringableTrait;

/**
 * WebSocket\Message\MessageHandler class.
 * Message/Frame handling.
 */
class MessageHandler implements LoggerAwareInterface, Stringable
{
    use StringableTrait;

    private const DEFAULT_SIZE = 4096;

    private $frameHandler;
    private $logger;
    private $readBuffer;

    public function __construct(FrameHandler $frameHandler)
    {
        $this->frameHandler = $frameHandler;
        $this->setLogger(new NullLogger());
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
        $this->frameHandler->setLogger($logger);
    }

    // Push message
    public function push(Message $message, int $size = self::DEFAULT_SIZE): Message
    {
        $frames = $message->getFrames($size);
        foreach ($frames as $frame) {
            $this->frameHandler->push($frame);
        }
        $this->logger->info("[message-handler] Pushed {$message}", [
            'opcode' => $message->getOpcode(),
            'content-length' => $message->getLength(),
            'frames' => count($frames),
        ]);
        return $message;
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
        switch ($payload_opcode) {
            case 'text':
                $message = new Text();
                break;
            case 'binary':
                $message = new Binary();
                break;
            case 'ping':
                $message = new Ping();
                break;
            case 'pong':
                $message = new Pong();
                break;
            case 'close':
                $message = new Close();
                break;
            default:
                throw new BadOpcodeException("Invalid opcode '{$payload_opcode}' provided");
        }
        $message->setPayload($payload);

        $this->logger->info("[message-handler] Pulled {$message}", [
            'opcode' => $message->getOpcode(),
            'content-length' => $message->getLength(),
            'frames' => $frames,
        ]);

        return $message;
    }
}
