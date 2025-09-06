<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Runtime;

use Closure;
use Phrity\Net\{
    StreamCollection,
    StreamException,
    StreamInterface,
};
use WebSocket\Exception\ExceptionInterface;

/**
 * WebSocket\Runtime\Watcher class.
 * Watches active streams for incoming data.
 */
class Watcher
{
    private StreamCollection $streamCollection;
    /** @var array<string, Closure> $selects */
    private array $selects = [];

    public function __construct(StreamCollection $streamCollection)
    {
        $this->streamCollection = $streamCollection;
    }

    public function attach(string $key, StreamInterface $attach, Closure $onSelect): void
    {
        $this->selects[$key] = $onSelect;
        $this->streamCollection->attach($attach, $key);
    }

    public function detach(string $key): void
    {
        if (array_key_exists($key, $this->selects)) {
            unset($this->selects[$key]);
        }
        $this->streamCollection->detach($key);
    }

    /**
     * @throws ExceptionInterface
     * @throws StreamException
     */
    public function watch(float $timeout): void
    {
        $readables = $this->streamCollection->waitRead($timeout);
        foreach ($readables as $key => $readable) {
            /**
             * @throws ExceptionInterface
             * @throws StreamException
             */
            call_user_func($this->selects[$key], $key, $readable);
        }
    }

    public function isEmpty(): bool
    {
        return $this->streamCollection->count() == 0;
    }
}
