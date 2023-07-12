<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

namespace WebSocket\Http;

use BadMethodCallException;
use InvalidArgumentException;
use Psr\Http\Message\{
    MessageInterface,
    StreamInterface
};

/**
 * Phrity\WebSocket\Http\Message class.
 * Only used for handshake procedure.
 */
abstract class Message implements MessageInterface
{
    protected $version = '1.1';
    protected $headers = [];

    /**
     * Retrieves the HTTP protocol version as a string.
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion(): string
    {
        return $this->version;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     * @param string $version HTTP protocol version
     * @return static
     */
    public function withProtocolVersion(string $version): self
    {
        $new = clone $this;
        $new->version = $version;
        return $new;
    }

    /**
     * Retrieves all message header values.
     * @return string[][] Returns an associative array of the message's headers.
     */
    public function getHeaders(): array
    {
        return array_merge(...array_values($this->headers));
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     * @param string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header.
     */
    public function hasHeader(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->headers);
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     * @param string $name Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given header.
     */
    public function getHeader(string $name): array
    {
        return $this->hasHeader($name)
            ? array_merge(...array_values($this->headers[strtolower($name)] ?: []))
            : [];
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header.
     */
    public function getHeaderLine(string $name): string
    {
        return implode(',', $this->getHeader($name));
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader(string $name, $value): self
    {
        $new = clone $this;
        if ($this->hasHeader($name)) {
            unset($new->headers[strtolower($name)]);
        }
        $new->handleHeader($name, $value);
        return $new;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names.
     * @throws \InvalidArgumentException for invalid header values.
     */
    public function withAddedHeader(string $name, $value): self
    {
        $new = clone $this;
        $new->handleHeader($name, $value);
        return $new;
    }

    /**
     * Return an instance without the specified header.
     * @param string $name Case-insensitive header field name to remove.
     * @return static
     */
    public function withoutHeader(string $name): self
    {
        $new = clone $this;
        if ($this->hasHeader($name)) {
            unset($new->headers[strtolower($name)]);
        }
        return $new;
    }

    /**
     * Not implemented, WebSocket only use headers.
     */
    public function getBody(): StreamInterface
    {
        throw new BadMethodCallException("Not implemented.");
    }

    /**
     * Not implemented, WebSocket only use headers.
     */
    public function withBody(StreamInterface $body): self
    {
        throw new BadMethodCallException("Not implemented.");
    }

    public function getAsArray(): array
    {
        $lines = [];
        foreach ($this->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $lines[] = "{$name}: {$value}";
            }
        }
        return $lines;
    }

    private function handleHeader(string $name, $value): void
    {
        // @todo: Add all available characters, these are just some of them.
        if (!preg_match('|^[0-9a-zA-Z#_-]+$|', $name)) {
            throw new InvalidArgumentException("'{$name}' is not a valid header field name.");
        }
        $value = is_array($value) ? $value : [$value];
        foreach ($value as $content) {
            if (!(is_string($content) || is_numeric($content)) || empty($content = trim($content))) {
                throw new InvalidArgumentException("Invalid header value(s) provided.");
            }
            $this->headers[strtolower($name)][$name][] = $content;
        }
    }
}
