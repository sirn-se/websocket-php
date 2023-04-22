<?php

/**
 * File for Phrity\WebSocket\Http\Request class
 * @package Phrity > WebSocket > Http
 */

namespace WebSocket\Http;

use Phrity\Net\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

/**
 * Phrity\WebSocket\Http\Request class.
 */
class Request extends Message implements RequestInterface
{
    private $target;
    private $method;
    private $uri;

    public function __construct(string $method = '', $uri = null)
    {
        $this->uri = $uri instanceof Uri ? $uri : new Uri((string)$uri);
        $this->method = $method;
        if ($this->uri->getHost()) {
            $this->headers = ['host' => ['host' => [$this->uri->getHost()]]];
        }
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
    public function withRequestTarget($requestTarget): self
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
    public function withMethod($method): self
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
    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        $new = clone $this;
        $new->uri = $uri instanceof Uri ? $uri : new Uri((string)$uri);
        if (!$preserveHost || !$new->hasHeader('host')) {
            if (isset($new->headers['host'])) {
                unset($new->headers['host']);
            }
            if ($host = $uri->getHost()) {
                $new->headers = array_merge(['host' => ['host' => [$host]]], $new->headers);
            }
        }
        return $new;
    }

    public function parse(string $data): self
    {
        list ($head, $body) = explode("\r\n\r\n", $data);
        $headers = array_filter(explode("\r\n", $head));
        $status = array_shift($headers);

        preg_match('!^(?P<method>[A-Z]+) (?P<path>[^ ]*) HTTP/(?P<protocol>[0-9/.]+)!', $status, $matches);
        if (empty($matches)) {
            // @todo: handle error
            throw new RuntimeException('Invalid http request');
        }


        $path = $matches['path'];

        $request = $this
            ->withProtocolVersion($matches['protocol'])
            ->withMethod($matches['method']);

        foreach ($headers as $header) {
            $parts = explode(':', $header, 2);
            $request = $request->withHeader($parts[0], $parts[1]);
        }
        $host = new Uri("//{$request->getHeaderLine('host')}{$path}");
        $uri = $request->getUri()
            ->withHost($host->getHost())
            ->withPath($host->getPath())
            ->withQuery($host->getQuery());
        return $request->withUri($uri);
    }

    public function render(): string
    {
        $data = "GET {$this->getRequestTarget()} HTTP/{$this->getProtocolVersion()}\r\n";
        $data .= parent::render();
        return $data;
    }
}
