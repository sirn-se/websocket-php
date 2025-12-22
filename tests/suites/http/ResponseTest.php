<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Http;

use BadMethodCallException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Phrity\Net\StreamFactory;
use Phrity\Net\Uri;
use Psr\Http\Message\{
    ResponseInterface,
    UriInterface
};
use WebSocket\Http\Response;

/**
 * Test case for WebSocket\Http\Response.
 */
class ResponseTest extends TestCase
{
    public function setUp(): void
    {
        error_reporting(-1);
    }

    public function testEmptyResponse(): void
    {
        $response = new Response();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $response->getReasonPhrase());
        $this->assertEquals('1.1', $response->getProtocolVersion());
        $this->assertEquals([], $response->getHeaders());
        $this->assertFalse($response->hasHeader('none'));
        $this->assertEquals([], $response->getHeader('none'));
        $this->assertEquals('', $response->getHeaderLine('none'));
    }

    public function testCodeResponse(): void
    {
        $response = new Response(404, 'Not Found');
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Not Found', $response->getReasonPhrase());
    }

    public function testCodeReasonResponse(): void
    {
        $response = new Response(400, 'Custom reason phrase');
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Custom reason phrase', $response->getReasonPhrase());
    }

    public function testImmutability(): void
    {
        $response = new Response();

        $responseClone = $response->withProtocolVersion('1.0');
        $this->assertNotSame($responseClone, $response);
        $this->assertEquals('1.0', $responseClone->getProtocolVersion());

        $responseClone = $response->withStatus(500);
        $this->assertNotSame($responseClone, $response);
        $this->assertEquals(500, $responseClone->getStatusCode());
        $this->assertEquals('Internal Server Error', $responseClone->getReasonPhrase());

        $responseClone = $response->withHeader('Test-Header', 'Test-Value');
        $this->assertNotSame($responseClone, $response);
        $this->assertEquals(['Test-Value'], $responseClone->getHeader('Test-Header'));
    }

    // @todo Should fail
    public function testConstructStatus(): void
    {
        $response = new Response(99);
        $this->assertEquals(99, $response->getStatusCode());
    }

    public function testWithStatusError(): void
    {
        $response = new Response();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage(
            "Status code has to be an integer between 100 and 599. A status code of 99 was given"
        );
        $response->withStatus(99);
    }

    public function testGetBody(): void
    {
        $response = new Response();
        $this->assertEmpty($response->getBody()->getContents());
    }

    public function testWithBody(): void
    {
        $response = new Response();
        $factory = new StreamFactory();
        $response_2 = $response->withBody($factory->createStream());
        $this->assertNotSame($response, $response_2);
    }

    // @todo Should fail
    public function testHeaderName(): void
    {
        $response = new Response();
        $response = $response->withHeader('.', 'invalid name');
        $this->assertEquals('invalid name', $response->getHeaderLine('.'));
    }
}
