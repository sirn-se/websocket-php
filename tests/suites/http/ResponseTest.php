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
use Stringable;
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
        $this->assertInstanceOf(Stringable::class, $response);
        $this->assertEquals('WebSocket\Http\Response(200)', "{$response}");
        $this->assertEquals([
            'HTTP/1.1 200 OK',
        ], $response->getAsArray());
    }

    public function testCodeResponse(): void
    {
        $response = new Response(404);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Not Found', $response->getReasonPhrase());
        $this->assertEquals('WebSocket\Http\Response(404)', "{$response}");
        $this->assertEquals([
            'HTTP/1.1 404 Not Found',
        ], $response->getAsArray());
    }

    public function testCodeReasonResponse(): void
    {
        $response = new Response(400, 'Custom reason phrase');
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Custom reason phrase', $response->getReasonPhrase());
        $this->assertEquals('WebSocket\Http\Response(400)', "{$response}");
        $this->assertEquals([
            'HTTP/1.1 400 Custom reason phrase',
        ], $response->getAsArray());
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
        $this->assertEquals([
            'HTTP/1.1 200 OK',
            'Test-Header: Test-Value',
        ], $responseClone->getAsArray());
    }

    public function testConstructStatusError(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Invalid status code '99' provided.");
        $response = new Response(99);
    }

    public function testWithStatusError(): void
    {
        $response = new Response();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Invalid status code '99' provided.");
        $response->withStatus(99);
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

    public function testHeaderNameError(): void
    {
        $response = new Response();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("'.' is not a valid header field name.");
        $response->withHeader('.', 'invalid name');
    }
}
