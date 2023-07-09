<?php

/**
 * Copyright (C) 2014-2023 Textalk and contributors.
 *
 * This file is part of Websocket PHP and is free software under the ISC License.
 * License text: https://raw.githubusercontent.com/sirn-se/websocket-php/master/COPYING.md
 */

declare(strict_types=1);

namespace WebSocket\Test\Http;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use Phrity\Net\StreamFactory;
use Phrity\Net\Uri;
use Psr\Http\Message\{
    ResponseInterface,
    UriInterface
};
use WebSocket\Http\{
    Message,
    Response
};

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
        $this->assertInstanceOf(Message::class, $response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());
        $this->assertEquals('1.1', $response->getProtocolVersion());
        $this->assertEquals([], $response->getHeaders());
        $this->assertFalse($response->hasHeader('none'));
        $this->assertEquals([], $response->getHeader('none'));
        $this->assertEquals('', $response->getHeaderLine('none'));
        $this->assertEquals([
            'HTTP/1.1 200 OK',
        ], $response->getAsArray());
    }

    public function testCodeResponse(): void
    {
        $response = new Response(404);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Not Found', $response->getReasonPhrase());
        $this->assertEquals([
            'HTTP/1.1 404 Not Found',
        ], $response->getAsArray());
    }

    public function testCodeReasonResponse(): void
    {
        $response = new Response(400, 'Custom reason phrase');
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Custom reason phrase', $response->getReasonPhrase());
        $this->assertEquals([
            'HTTP/1.1 400 Custom reason phrase',
        ], $response->getAsArray());
    }

    public function testImmutability(): void
    {
        $response = new Response();

        $response_clone = $response->withProtocolVersion('1.0');
        $this->assertNotSame($response_clone, $response);
        $this->assertEquals('1.0', $response_clone->getProtocolVersion());

        $response_clone = $response->withStatus(500);
        $this->assertNotSame($response_clone, $response);
        $this->assertEquals(500, $response_clone->getStatusCode());
        $this->assertEquals('Internal Server Error', $response_clone->getReasonPhrase());

        $response_clone = $response->withHeader('Test-Header', 'Test-Value');
        $this->assertNotSame($response_clone, $response);
        $this->assertEquals(['Test-Value'], $response_clone->getHeader('Test-Header'));
        $this->assertEquals([
            'HTTP/1.1 200 OK',
            'Test-Header: Test-Value',
        ], $response_clone->getAsArray());
    }

    public function testGetBodyError(): void
    {
        $response = new Response();
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Not implemented.');
        $response->getBody();
    }

    public function testWithBodyError(): void
    {
        $response = new Response();
        $factory = new StreamFactory();
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Not implemented.');
        $response->withBody($factory->createStream());
    }
}
