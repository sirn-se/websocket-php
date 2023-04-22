<?php

/**
 * File for Phrity\WebSocket\Http\Response class
 * @package Phrity > WebSocket > Http
 */

namespace WebSocket\Http;

use Phrity\Net\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

/**
 * Phrity\WebSocket\Http\Response class.
 */
class Response extends Message implements ResponseInterface
{
    private static $codes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Content Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Content',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        100 => 'Continue',
        100 => 'Continue',
        100 => 'Continue',
        100 => 'Continue',
        100 => 'Continue',
        100 => 'Continue',
        100 => 'Continue',
        100 => 'Continue',
    ];

    private $code;
    private $reason;

    public function __construct(int $code = 200, string $reasonPhrase = '')
    {
        $this->code = $code;
        $this->reason = $reasonPhrase;
    }

    /**
     * Gets the response status code.
     * @return int Status code.
     */
    public function getStatusCode(): int
    {
        return $this->code;
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     * @param int $code The 3-digit integer result code to set.
     * @param string $reasonPhrase The reason phrase to use.
     * @return static
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = ''): self
    {
        $new = clone $this;
        $new->code = $code;
        $new->reason = $reasonPhrase;
        return $new;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     * @return string Reason phrase; must return an empty string if none present.
     */
    public function getReasonPhrase(): string
    {
        return $this->reason ?: self::$codes[$this->code];
    }

    public function parse(string $data): self
    {
        list ($head, $body) = explode("\r\n\r\n", $data);
        $headers = array_filter(explode("\r\n", $head));
        $status = array_shift($headers);

        preg_match('!^HTTP/(?P<version>[0-9/.]+) (?P<code>[0-9]*) (?P<reason>.*)!', $status, $matches);
        if (empty($matches)) {
            // @todo: handle error
            throw new RuntimeException('Invalid http request');
        }
        $response = $this
            ->withProtocolVersion($matches['version'])
            ->withStatus($matches['code'], $matches['reason']);
        foreach ($headers as $header) {
            $parts = explode(':', $header, 2);
            if (count($parts) == 2) {
                $response = $response->withHeader($parts[0], $parts[1]);
            }
        }
        return $response;
    }

    public function render(): string
    {
        $data = "HTTP/{$this->getProtocolVersion()} {$this->getStatusCode()} {$this->getReasonPhrase()}\r\n";
        $data .= parent::render();
        return $data;
    }
}
