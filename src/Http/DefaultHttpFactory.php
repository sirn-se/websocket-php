<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Http;

use Phrity\Http\HttpFactory;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * WebSocket\Http\DefaultHttpFactory
 * Only used for handshake procedure.
 */
class DefaultHttpFactory extends HttpFactory
{
    public function __construct()
    {
        $psrFactory = new Psr17Factory();
        parent::__construct(
            uriFactory: $psrFactory,
            requestFactory: $psrFactory,
            responseFactory: $psrFactory,
            serverRequestFactory: $psrFactory,
            streamFactory: $psrFactory,
        );
    }
}
