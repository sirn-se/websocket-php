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

    public function __construct(StreamCollection $streamCollection)
    {
        $this->streamCollection = $streamCollection;
    }

    public function attach(SelectableInterface $attach): void
    {
        $this->streamCollection->attach($attach, $attach->getIdentity());
    }

    public function detach(string $key): void
    {
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
            if ($readable instanceof SelectableInterface) {
                $readable->onSelect();
            }
        }
    }

    public function isEmpty(): bool
    {
        return $this->streamCollection->count() == 0;
    }
}
