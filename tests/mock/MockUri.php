<?php

/**
 * Copyright (C) 2014-2024 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Test;

use Psr\Http\Message\UriInterface;

/**
 * This class is used by phpunit tests to mock and track various socket/stream calls.
 */
class MockUri implements UriInterface
{
    public function __toString(): string
    {
        return "ws://test.com/path";
    }

    public function getScheme(): string
    {
        return 'ws';
    }


    public function getAuthority(): string
    {
        return 'test.com';
    }

    public function getUserInfo(): string
    {
        return '';
    }

    public function getHost(): string
    {
        return 'test.com';
    }

    public function getPort(): ?int
    {
        return 80;
    }

    public function getPath(): string
    {
        return '/path';
    }

    public function getQuery(): string
    {
        return '';
    }

    public function getFragment(): string
    {
        return '';
    }


    // ---------- PSR-7 setters ---------------------------------------------------------------------------------------

    public function withScheme($scheme): UriInterface
    {
        return clone $this;
    }

    public function withUserInfo($user, $password = null): UriInterface
    {
        return clone $this;
    }

    public function withHost($host): UriInterface
    {
        return clone $this;
    }

    public function withPort($port): UriInterface
    {
         return clone $this;
    }

    public function withPath($path): UriInterface
    {
        return clone $this;
    }

    public function withQuery($query): UriInterface
    {
        return clone $this;
    }

    public function withFragment($fragment): UriInterface
    {
        return clone $this;
    }
}
