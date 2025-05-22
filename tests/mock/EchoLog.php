<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Test;

use JsonSerializable;
use Psr\Log\{
    LoggerInterface,
    LoggerTrait
};
use Stringable;
use Throwable;

/**
 * Simple echo logger (only available when running in dev environment)
 */
class EchoLog implements LoggerInterface
{
    use LoggerTrait;

    private const FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;

    public function log($level, $message, array $context = [])
    {
        $message = $this->interpolate($message, $context);
        $contextString = json_encode($this->context($context), self::FLAGS);
        echo str_pad($level, 8) . " | {$message} {$contextString}\n";
    }

    /** @param array<string, mixed> $context */
    private function interpolate(string $message, array $context = []): string
    {
        // Build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        // Interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed> $context
     */
    private function context(array $context): array
    {
        return array_map(function ($item) {
            if (is_scalar($item)) {
                return $item;
            }
            if (is_array($item)) {
                return $this->context($item);
            }
            if ($item instanceof JsonSerializable) {
                $serialized = $item->jsonSerialize();
                if (is_scalar($serialized)) {
                    return $serialized;
                }
                return $this->context($serialized);
            }
            if ($item instanceof Throwable) {
                return $this->context(array_filter([
                    'class' => get_class($item),
                    'message' => $item->getMessage(),
                    'code' => $item->getCode(),
                    'previous' => $item->getPrevious(),
                ]));
            }
            if ($item instanceof Stringable) {
                return $item->__toString();
            }
            if (is_object($item)) {
                return get_class($item);
            }
            return gettype($item);
        }, $context);
    }
}
