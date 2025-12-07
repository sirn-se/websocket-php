<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Message;

use Stringable;
use WebSocket\Configuration;
use WebSocket\Exception\BadOpcodeException;
use WebSocket\Frame\{
    Frame,
    FrameHandler,
};
use WebSocket\Trait\{
    ConfigurationTrait,
    StringableTrait,
};

/**
 * WebSocket\Message\MessageHandler class.
 * Message/Frame handling.
 */
class MessageHandler implements Stringable
{
    use ConfigurationTrait;
    use StringableTrait;

    private const DEFAULT_SIZE = 4096;

    private FrameHandler $frameHandler;
    /** @var array<Frame> $frameBuffer */
    private array $frameBuffer = [];

    public function __construct(FrameHandler $frameHandler, Configuration|null $configuration = null)
    {
        $this->frameHandler = $frameHandler;
        $this->initConfiguration($configuration);
    }

    /**
     * Push message
     * @template T of Message
     * @param T $message
     * @param int<1, max> $size
     * @return T
     */
    public function push(Message $message, int $size = self::DEFAULT_SIZE): Message
    {
        $frames = $message->getFrames($size);
        foreach ($frames as $frame) {
            $this->frameHandler->push($frame);
        }
        $this->configuration->getLogger()->info('[message-handler] Pushed {message}', [
            'content-length' => $message->getLength(),
            'frames' => count($frames),
            'message' => (string)$message,
            'opcode' => $message->getOpcode(),
        ]);
        return $message;
    }

    // Pull message
    public function pull(): Message
    {
        do {
            $frame = $this->frameHandler->pull();
            if ($frame->isFinal()) {
                if ($frame->isContinuation()) {
                    $frames = array_merge($this->frameBuffer, [$frame]);
                    $this->frameBuffer = []; // Clear buffer
                } else {
                    $frames = [$frame];
                }
                return $this->createMessage($frames);
            }
            // Non-final frame - add to buffer for continuous reading
            $this->frameBuffer[] = $frame;
        } while (true);
    }

    /**
     * @param non-empty-array<Frame> $frames
     * @throws BadOpcodeException
     */
    private function createMessage(array $frames): Message
    {
        $opcode = $frames[0]->getOpcode() ?? null;
        $message = match ($opcode) {
            'text' => new Text(),
            'binary' => new Binary(),
            'ping' => new Ping(),
            'pong' => new Pong(),
            'close' => new Close(),
            default => throw new BadOpcodeException("Invalid opcode '{$opcode}' provided"),
        };
        $message->setPayload(array_reduce($frames, function (string $carry, Frame $item) {
            return $carry . $item->getPayload();
        }, ''));
        $message->setCompress($frames[0]->getRsv1() ?? false);
        $this->configuration->getLogger()->info('[message-handler] Pulled {message}', [
            'content-length' => $message->getLength(),
            'frames' => count($frames),
            'message' => (string)$message,
            'opcode' => $message->getOpcode(),
        ]);
        return $message;
    }
}
