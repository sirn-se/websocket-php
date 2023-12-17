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
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Phrity\Net\StreamFactory;
use Phrity\Net\Uri;
use Psr\Http\Message\{
    RequestInterface,
    UriInterface
};
use Stringable;
use WebSocket\Http\{
    Message,
    Request
};

/**
 * Test case for WebSocket\Http\Request.
 */
class RequestTest extends TestCase
{
    public function setUp(): void
    {
        error_reporting(-1);
    }

    public function testEmptyRequest(): void
    {
        $request = new Request();
        $this->assertInstanceOf(Request::class, $request);
        $this->assertInstanceOf(Message::class, $request);
        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertEquals('/', $request->getRequestTarget());
        $this->assertEquals('GET', $request->getMethod());
        $this->assertInstanceOf(UriInterface::class, $request->getUri());
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertEquals([], $request->getHeaders());
        $this->assertFalse($request->hasHeader('none'));
        $this->assertEquals([], $request->getHeader('none'));
        $this->assertEquals('', $request->getHeaderLine('none'));
        $this->assertInstanceOf(Stringable::class, $request);
        $this->assertEquals('WebSocket\Http\Request(GET )', "{$request}");
        $this->assertEquals([
            'GET / HTTP/1.1',
        ], $request->getAsArray());
    }

    public function testUriInstanceRequest(): void
    {
        $uri = new Uri('ws://test.com:123/a/path?a=b');
        $request = new Request('POST', $uri);
        $this->assertEquals('/a/path?a=b', $request->getRequestTarget());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertInstanceOf(UriInterface::class, $request->getUri());
        $this->assertEquals(['Host' => ['test.com:123']], $request->getHeaders());
        $this->assertTrue($request->hasHeader('Host'));
        $this->assertEquals(['test.com:123'], $request->getHeader('Host'));
        $this->assertEquals('test.com:123', $request->getHeaderLine('Host'));
        $this->assertEquals('WebSocket\Http\Request(POST ws://test.com:123/a/path?a=b)', "{$request}");
        $this->assertEquals([
            'POST /a/path?a=b HTTP/1.1',
            'Host: test.com:123',
        ], $request->getAsArray());
    }

    public function testUriStringRequest(): void
    {
        $request = new Request('POST', 'ws://test.com:123/a/path?a=b');
        $this->assertEquals('/a/path?a=b', $request->getRequestTarget());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertInstanceOf(UriInterface::class, $request->getUri());
        $this->assertEquals(['Host' => ['test.com:123']], $request->getHeaders());
        $this->assertTrue($request->hasHeader('Host'));
        $this->assertEquals(['test.com:123'], $request->getHeader('Host'));
        $this->assertEquals('test.com:123', $request->getHeaderLine('Host'));
        $this->assertEquals('WebSocket\Http\Request(POST ws://test.com:123/a/path?a=b)', "{$request}");
        $this->assertEquals([
            'POST /a/path?a=b HTTP/1.1',
            'Host: test.com:123',
        ], $request->getAsArray());
    }

    public function testImmutability(): void
    {
        $request = new Request();
        $request_clone = $request->withRequestTarget('/new/path?c=d');
        $this->assertNotSame($request_clone, $request);
        $this->assertEquals('/new/path?c=d', $request_clone->getRequestTarget());

        $request_clone = $request->withMethod('POST');
        $this->assertNotSame($request_clone, $request);
        $this->assertEquals('POST', $request_clone->getMethod());

        $request_clone = $request->withUri(new Uri('ws://test.com:123/a/path?a=b'));
        $this->assertNotSame($request_clone, $request);
        $this->assertEquals('/a/path?a=b', $request_clone->getRequestTarget());
        $this->assertEquals(['Host' => ['test.com:123']], $request_clone->getHeaders());

        $request_clone = $request->withProtocolVersion('1.0');
        $this->assertNotSame($request_clone, $request);
        $this->assertEquals('1.0', $request_clone->getProtocolVersion());

        $request_clone = $request->withHeader('Test-Header', 'Test-Value');
        $this->assertNotSame($request_clone, $request);
        $this->assertEquals(['Test-Value'], $request_clone->getHeader('Test-Header'));
    }

    public function testHeaders(): void
    {
        $request_1 = new Request('GET', 'ws://test.com:123/a/path?a=b');
        $this->assertEquals([
            'Host' => ['test.com:123'],
        ], $request_1->getHeaders());
        $this->assertEquals([
            'GET /a/path?a=b HTTP/1.1',
            'Host: test.com:123',
        ], $request_1->getAsArray());

        $request_2 = $request_1->withHeader('Test-Header', 'Test-Value-1');
        $this->assertNotSame($request_2, $request_1);
        $this->assertEquals([
            'Host' => ['test.com:123'],
            'Test-Header' => ['Test-Value-1'],
        ], $request_2->getHeaders());
        $this->assertEquals([
            'GET /a/path?a=b HTTP/1.1',
            'Host: test.com:123',
            'Test-Header: Test-Value-1',
        ], $request_2->getAsArray());

        $request_3 = $request_2->withHeader('Test-Header', 'Test-Value-2');
        $this->assertNotSame($request_3, $request_2);
        $this->assertEquals([
            'Host' => ['test.com:123'],
            'Test-Header' => ['Test-Value-2'],
        ], $request_3->getHeaders());
        $this->assertEquals([
            'GET /a/path?a=b HTTP/1.1',
            'Host: test.com:123',
            'Test-Header: Test-Value-2',
        ], $request_3->getAsArray());

        $request_4 = $request_3->withAddedHeader('Test-Header', 'Test-Value-3');
        $this->assertNotSame($request_4, $request_3);
        $this->assertEquals([
            'Host' => ['test.com:123'],
            'Test-Header' => ['Test-Value-2', 'Test-Value-3'],
        ], $request_4->getHeaders());
        $this->assertEquals([
            'GET /a/path?a=b HTTP/1.1',
            'Host: test.com:123',
            'Test-Header: Test-Value-2',
            'Test-Header: Test-Value-3',
        ], $request_4->getAsArray());

        $request_5 = $request_4->withoutHeader('Test-Header');
        $this->assertNotSame($request_5, $request_4);
        $this->assertEquals([
            'Host' => ['test.com:123'],
        ], $request_5->getHeaders());
        $this->assertEquals([
            'GET /a/path?a=b HTTP/1.1',
            'Host: test.com:123',
        ], $request_5->getAsArray());

        $request_6 = $request_5->withUri(new Uri('ws://another.com:456/new/path?a=b'));
        $this->assertNotSame($request_6, $request_5);
        $this->assertEquals([
            'Host' => ['another.com:456'],
        ], $request_6->getHeaders());
        $this->assertEquals([
            'GET /new/path?a=b HTTP/1.1',
            'Host: another.com:456',
        ], $request_6->getAsArray());

        $request_7 = $request_6->withUri(new Uri('ws://yetanother.com:789/new/path?a=b'), true);
        $this->assertNotSame($request_7, $request_6);
        $this->assertEquals([
            'Host' => ['another.com:456'],
        ], $request_7->getHeaders());
        $this->assertEquals([
            'GET /new/path?a=b HTTP/1.1',
            'Host: another.com:456',
        ], $request_6->getAsArray());
    }

    public function testGetBodyError(): void
    {
        $request = new Request();
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Not implemented.');
        $request->getBody();
    }

    public function testWithBodyError(): void
    {
        $request = new Request();
        $factory = new StreamFactory();
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Not implemented.');
        $request->withBody($factory->createStream());
    }

    public function testHaederNameError(): void
    {
        $request = new Request();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("'.' is not a valid header field name.");
        $request->withHeader('.', 'invaid name');
    }

    public function testHaederValueError(): void
    {
        $request = new Request();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Invalid header value(s) provided.");
        $request->withHeader('name', '');
    }
}
