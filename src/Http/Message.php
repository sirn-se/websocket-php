<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Http;

use BadMethodCallException;
use InvalidArgumentException;
use Psr\Http\Message\{
    MessageInterface,
    StreamInterface
};
use Stringable;
use WebSocket\Trait\StringableTrait;

/**
 * Phrity\WebSocket\Http\Message class.
 * Only used for handshake procedure.
 */
abstract class Message implements MessageInterface, Stringable
{
    use StringableTrait;

    protected string $version = '1.1';
    /** @var array<string, array<mixed>> $headers */
    private array $headers = [];

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
     */
    public function withHeader(string $name, mixed $value): self
    {
        $new = clone $this;
        $new->removeHeader($name);
        $new->handleHeader($name, $value);
        return $new;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return static
     */
    public function withAddedHeader(string $name, mixed $value): self
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
        $new->removeHeader($name);
        return $new;
    }

    /**
     * Not implemented, WebSocket only use headers.
     * @throws BadMethodCallException
     */
    public function getBody(): StreamInterface
    {
        throw new BadMethodCallException("Not implemented.");
    }

    /**
     * Not implemented, WebSocket only use headers.
     * @throws BadMethodCallException
     */
    public function withBody(StreamInterface $body): self
    {
        throw new BadMethodCallException("Not implemented.");
    }

    /** @return array<string> */
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

    /**
     * @throws InvalidArgumentException
     */
    protected function handleHeader(string $name, mixed $value): void
    {
        if (!preg_match('|^[0-9a-zA-Z#_-]+$|', $name)) {
            throw new InvalidArgumentException("'{$name}' is not a valid header field name.");
        }
        $value = is_array($value) ? $value : [$value];
        foreach ($value as $content) {
            if (!is_string($content) && !is_numeric($content)) {
                throw new InvalidArgumentException("Invalid header value(s) provided.");
            }
            $this->headers[strtolower($name)][$name][] = trim((string)$content);
        }
    }

    protected function removeHeader(string $name): void
    {
        if ($this->hasHeader($name)) {
            unset($this->headers[strtolower($name)]);
        }
    }
}
