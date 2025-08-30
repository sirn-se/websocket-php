<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket;

require dirname(__DIR__) . '/vendor/autoload.php';

if (extension_loaded('xdebug')) {
    die("Tests can not be run with xdebug installed.\n");
}
