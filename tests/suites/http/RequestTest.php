<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Http;

use BadMethodCallException;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Phrity\Net\StreamFactory;
use Phrity\Net\Uri;
use Psr\Http\Message\{
    RequestInterface,
    UriInterface
};
use WebSocket\Http\Request;

/**
 * Test case for WebSocket\Http\Request.
 */
class RequestTest extends TestCase
{
    public function testEmptyRequest(): void
    {
        $request = new Request();
        $this->assertInstanceOf(Request::class, $request);
        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertEquals('/', $request->getRequestTarget());
        $this->assertEquals('GET', $request->getMethod());
        $this->assertInstanceOf(UriInterface::class, $request->getUri());
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertEquals([], $request->getHeaders());
        $this->assertFalse($request->hasHeader('none'));
        $this->assertEquals([], $request->getHeader('none'));
        $this->assertEquals('', $request->getHeaderLine('none'));
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
    }

    public function testImmutability(): void
    {
        $request = new Request();
        $requestClone = $request->withRequestTarget('/new/path?c=d');
        $this->assertNotSame($requestClone, $request);
        $this->assertEquals('/new/path?c=d', $requestClone->getRequestTarget());

        $requestClone = $request->withMethod('POST');
        $this->assertNotSame($requestClone, $request);
        $this->assertEquals('POST', $requestClone->getMethod());

        $requestClone = $request->withUri(new Uri('ws://test.com:123/a/path?a=b'));
        $this->assertNotSame($requestClone, $request);
        $this->assertEquals('/a/path?a=b', $requestClone->getRequestTarget());
        $this->assertEquals(['Host' => ['test.com:123']], $requestClone->getHeaders());

        $requestClone = $request->withProtocolVersion('1.0');
        $this->assertNotSame($requestClone, $request);
        $this->assertEquals('1.0', $requestClone->getProtocolVersion());

        $requestClone = $request->withHeader('Test-Header', 'Test-Value');
        $this->assertNotSame($requestClone, $request);
        $this->assertEquals(['Test-Value'], $requestClone->getHeader('Test-Header'));
    }

    public function testHeaders(): void
    {
        $request1 = new Request('GET', 'ws://test.com:123/a/path?a=b');
        $this->assertEquals([
            'Host' => ['test.com:123'],
        ], $request1->getHeaders());

        $request2 = $request1->withHeader('Test-Header', 'Test-Value-1');
        $this->assertNotSame($request2, $request1);
        $this->assertEquals([
            'Host' => ['test.com:123'],
            'Test-Header' => ['Test-Value-1'],
        ], $request2->getHeaders());

        $request3 = $request2->withHeader('Test-Header', 'Test-Value-2');
        $this->assertNotSame($request3, $request2);
        $this->assertEquals([
            'Host' => ['test.com:123'],
            'Test-Header' => ['Test-Value-2'],
        ], $request3->getHeaders());

        $request4 = $request3->withAddedHeader('Test-Header', 'Test-Value-3');
        $this->assertNotSame($request4, $request3);
        $this->assertEquals([
            'Host' => ['test.com:123'],
            'Test-Header' => ['Test-Value-2', 'Test-Value-3'],
        ], $request4->getHeaders());

        $request5 = $request4->withoutHeader('Test-Header');
        $this->assertNotSame($request5, $request4);
        $this->assertEquals([
            'Host' => ['test.com:123'],
        ], $request5->getHeaders());

        $request6 = $request5->withUri(new Uri('ws://another.com:456/new/path?a=b'));
        $this->assertNotSame($request6, $request5);
        $this->assertEquals([
            'Host' => ['another.com:456'],
        ], $request6->getHeaders());

        $request7 = $request6->withUri(new Uri('ws://yetanother.com:789/new/path?a=b'), true);
        $this->assertNotSame($request7, $request6);
        $this->assertEquals([
            'Host' => ['another.com:456'],
        ], $request7->getHeaders());
    }

    public function testGetBody(): void
    {
        $request = new Request();
        $this->assertEmpty($request->getBody()->getContents());
    }

    public function testWithBody(): void
    {
        $request = new Request();
        $factory = new StreamFactory();
        $request_2 = $request->withBody($factory->createStream());
        $this->assertNotSame($request, $request_2);
    }

    // @todo Should fail
    public function testConstructMethod(): void
    {
        $request = new Request('INVALID');
        $this->assertEquals('INVALID', $request->getMethod());
    }

    // @todo Should fail
    public function testWithMethod(): void
    {
        $request = new Request();
        $request->withMethod('INVALID');
        $this->assertEquals('GET', $request->getMethod());
    }

    // @todo Should fail
    public function testHeaderName(): void
    {
        $request = new Request();
        $request = $request->withHeader('.', 'invalid name');
        $this->assertEquals('invalid name', $request->getHeaderLine('.'));
    }

    #[DataProvider('provideNotCompatibleValues')]
    public function testHeaderValueNotCompatible(mixed $value): void
    {
        $request = new Request();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Header values must be RFC 7230 compatible strings");
        $request->withHeader('name', $value);
    }

    public static function provideNotCompatibleValues(): Generator
    {
        yield [[null]];
        yield [[[0]]];
    }

    #[DataProvider('provideEmptyValues')]
    public function testHeaderValueEmpty(mixed $value): void
    {
        $request = new Request();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage("Header values must be a string or an array of strings, empty array given");
        $request->withHeader('name', $value);
    }

    public static function provideEmptyValues(): Generator
    {
        yield [[]];
    }

    /** @param array<mixed> $expected */
    #[DataProvider('provideValidHeaderValues')]
    public function testHeaderValueValidVariants(mixed $value, array $expected): void
    {
        $request = new Request();
        $request = $request->withHeader('name', $value);
        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals($expected, $request->getHeader('name'));
    }

    public static function provideValidHeaderValues(): Generator
    {
        yield ['', ['']];
        yield ['  ', ['']];
        yield [['0', ''],  ['0', '']];
        yield ['null', ['null']];
        yield ['0  ', ['0']];
        yield ['  0', ['0']];
        yield [['0', '1'], ['0', '1']];
        yield [0, ['0']];
    }
}
