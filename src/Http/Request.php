<?php

/**
 * Copyright (C) 2014-2024 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Http;

use Phrity\Net\Uri;
use Psr\Http\Message\{
    RequestInterface,
    UriInterface
};
use RuntimeException;

/**
 * WebSocket\Http\Request class.
 * Only used for handshake procedure.
 */
class Request extends Message implements RequestInterface
{
    private $target;
    private $method;
    private $uri;

    public function __construct(string $method = 'GET', UriInterface|string|null $uri = null)
    {
        $this->uri = $uri instanceof Uri ? $uri : new Uri((string)$uri);
        $this->method = $method;
        $this->headers = ['host' => ['Host' => [$this->formatHostHeader($this->uri)]]];
    }

    /**
     * Retrieves the message's request target.
     * @return string
     */
    public function getRequestTarget(): string
    {
        if ($this->target) {
            return $this->target;
        }
        $uri = (new Uri())->withPath($this->uri->getPath())->withQuery($this->uri->getQuery());
        return $uri->toString(Uri::ABSOLUTE_PATH);
    }

    /**
     * Return an instance with the specific request-target.
     * @param mixed $requestTarget
     * @return static
     */
    public function withRequestTarget(mixed $requestTarget): self
    {
        $new = clone $this;
        $new->target = $requestTarget;
        return $new;
    }

    /**
     * Retrieves the HTTP method of the request.
     * @return string Returns the request method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Return an instance with the provided HTTP method.
     * @param string $method Case-sensitive method.
     * @return static
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod(string $method): self
    {
        $new = clone $this;
        $new->method = $method;
        return $new;
    }

    /**
     * Retrieves the URI instance.
     * This method MUST return a UriInterface instance.
     * @return UriInterface Returns a UriInterface instance representing the URI of the request.
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     * @return static
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): self
    {
        $new = clone $this;
        $new->uri = $uri instanceof Uri ? $uri : new Uri((string)$uri);
        if (!$preserveHost || !$new->hasHeader('host')) {
            if (isset($new->headers['host'])) {
                unset($new->headers['host']);
            }
            $new->headers = array_merge(['host' => ['Host' => [$this->formatHostHeader($uri)]]], $new->headers);
        }
        return $new;
    }

    public function __toString(): string
    {
        return $this->stringable('%s %s', $this->getMethod(), $this->getUri());
    }

    public function getAsArray(): array
    {
        return array_merge([
            "{$this->getMethod()} {$this->getRequestTarget()} HTTP/{$this->getProtocolVersion()}",
        ], parent::getAsArray());
    }

    private function formatHostHeader(Uri $uri): string
    {
        $host = $uri->getHost();
        $port = $uri->getPort();
        return $host && $port ? "{$host}:{$port}" : $host;
    }
}
