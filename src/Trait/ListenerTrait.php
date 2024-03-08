<?php

/**
 * Copyright (C) 2014-2024 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Trait;

use Closure;

/**
 * WebSocket\Trait\ListenerTrait trait.
 * Provides listener functions.
 */
trait ListenerTrait
{
    private $listeners = [];

    public function onConnect(Closure $closure): self
    {
        $this->listeners['connect'] = $closure;
        return $this;
    }

    public function onDisconnect(Closure $closure): self
    {
        $this->listeners['disconnect'] = $closure;
        return $this;
    }

    public function onText(Closure $closure): self
    {
        $this->listeners['text'] = $closure;
        return $this;
    }

    public function onBinary(Closure $closure): self
    {
        $this->listeners['binary'] = $closure;
        return $this;
    }

    public function onPing(Closure $closure): self
    {
        $this->listeners['ping'] = $closure;
        return $this;
    }

    public function onPong(Closure $closure): self
    {
        $this->listeners['pong'] = $closure;
        return $this;
    }

    public function onClose(Closure $closure): self
    {
        $this->listeners['close'] = $closure;
        return $this;
    }

    public function onError(Closure $closure): self
    {
        $this->listeners['error'] = $closure;
        return $this;
    }

    public function onTick(Closure $closure): self
    {
        $this->listeners['tick'] = $closure;
        return $this;
    }

    private function dispatch(string $type, array $args = []): void
    {
        if (array_key_exists($type, $this->listeners)) {
            $closure = $this->listeners[$type];
            call_user_func_array($closure, $args);
        }
    }
}
