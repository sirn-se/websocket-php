<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Test;

use Phrity\Logger\Console\{
    ConsoleLogger,
    Verbosity,
};

/**
 * @deprecated This file will be removed in v4
 */
class EchoLog extends ConsoleLogger
{
    public function __construct(Verbosity $verbosity = Verbosity::Debug, string $format = '{level} | {message}')
    {
        parent::__construct($verbosity, $format);
        trigger_error('EchoLog is deprecated and will be removed in v4. Use another logger.', E_USER_DEPRECATED);
    }
}
