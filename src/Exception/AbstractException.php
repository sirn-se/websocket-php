<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Exception;

use Phrity\Util\Interpolator\InterpolatorTrait;
use Throwable;

/**
 * WebSocket\Exception\AbstractException abstract class.
 * Core exception for repo
 * @todo: Will extend RuntimeException directly in v4
 */
abstract class AbstractException extends Exception implements ExceptionInterface
{
    use InterpolatorTrait;

    protected static string $defaultMessage = 'Unspecified error';

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string|null $message = null,
        int $code = 0,
        Throwable|null $previous = null,
        array $context = [],
    ) {
        $message = $this->interpolate($message ?? static::$defaultMessage, $context);
        parent::__construct($message, $code, $previous);
    }
}
