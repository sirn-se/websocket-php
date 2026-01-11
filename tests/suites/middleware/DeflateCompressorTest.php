<?php

/**
 * Copyright (C) 2014-2025 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

declare(strict_types=1);

namespace WebSocket\Test\Middleware;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Phrity\Net\Mock\SocketStream;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface,
};
use RangeException;
use RuntimeException;
use Stringable;
use WebSocket\Connection;
use WebSocket\Middleware\CompressionExtension;
use WebSocket\Middleware\CompressionExtension\DeflateCompressor;
use WebSocket\Test\MockStreamTrait;

/**
 * Test case for WebSocket\Middleware\CompressionExtension\DeflateCompressor
 * @phpstan-import-type Config from DeflateCompressor
 */
class DeflateCompressorTest extends TestCase
{
    use MockStreamTrait;

    private Psr17Factory $psrFactory;

    public function setUp(): void
    {
        $this->setUpStack();
        $this->psrFactory = new Psr17Factory();
    }

    public function tearDown(): void
    {
        $this->tearDownStack();
    }

    public function testClientDefault(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = new Connection($stream, false, false);

        $compressor = new DeflateCompressor();
        $this->assertInstanceOf(Stringable::class, $compressor);
        $this->assertEquals('WebSocket\Middleware\CompressionExtension\DeflateCompressor', "{$compressor}");
        $middleware = new CompressionExtension($compressor);
        $this->assertInstanceOf(Stringable::class, $middleware);
        $this->assertEquals('WebSocket\Middleware\CompressionExtension', "{$middleware}");
        $connection->addMiddleware($middleware);

        // Send headers to Server
        $this->expectSocketStreamWrite()->addAssert(
            function (string $method, array $params): void {
                $this->assertEquals(
                    "GET / HTTP/1.1\r\nHost: test.url\r\n"
                    . "Sec-WebSocket-Extensions: permessage-deflate\r\n\r\n",
                    $params[0]
                );
            }
        );
        $request = $this->psrFactory->createRequest('GET', 'ws://test.url');
        $request = $connection->pushHttp($request);
        $this->assertEquals(['permessage-deflate'], $request->getHeader('Sec-WebSocket-Extensions'));
        $this->assertNull($connection->getMeta('compressionExtension.compressor'));
        $this->assertNull($connection->getMeta('compressionExtension.configuration'));

        // Receive heders from Server
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 200 OK\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Extensions: permessage-deflate\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $response = $connection->pullHttp();
        $this->assertEquals(['permessage-deflate'], $response->getHeader('Sec-WebSocket-Extensions'));
        $this->assertEquals($compressor, $connection->getMeta('compressionExtension.compressor'));
        $this->assertEquals((object)[
            'compressor' => 'permessage-deflate',
            'isServer' => false,
            'serverNoContextTakeover' => false,
            'clientNoContextTakeover' => false,
            'serverMaxWindowBits' => 15,
            'clientMaxWindowBits' => 15,
            'deflator' => null,
            'inflator' => null,
        ], $connection->getMeta('compressionExtension.configuration'));

        // Send messages
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('wQ9yyywqLlHITS0uTkxPBQA='), $params[0]);
        });
        $connection->text('First message');
        $deflator = $this->getConfiguration($connection)->deflator;

        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('wgoKTk3Oz0uB8QAA'), $params[0]);
        });
        $connection->binary('Second message');
        $this->assertNotNull($this->getConfiguration($connection)->deflator);
        $this->assertNull($this->getConfiguration($connection)->inflator);
        $this->assertSame($deflator, $this->getConfiguration($connection)->deflator);

        // Receive messages
        $this->expectWsReadMessage('wQ8=', 'csssKi5RyE0tLk5MTwUA');
        $message = $connection->pullMessage();
        $this->assertEquals('text', $message->getOpcode());
        $this->assertEquals('First message', $message->getContent());
        $inflator = $this->getConfiguration($connection)->inflator;

        $this->expectWsReadMessage('wgo=', 'Ck5Nzs9LgfEAAA==');
        $message = $connection->pullMessage();
        $this->assertEquals('binary', $message->getOpcode());
        $this->assertEquals('Second message', $message->getContent());
        $this->assertNotNull($this->getConfiguration($connection)->deflator);
        $this->assertNotNull($this->getConfiguration($connection)->inflator);
        $this->assertSame($inflator, $this->getConfiguration($connection)->inflator);

        $this->expectSocketStreamClose();
        $connection->disconnect();
    }

    public function testServerDefault(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = new Connection($stream, false, false);

        $compressor = new DeflateCompressor();
        $this->assertInstanceOf(Stringable::class, $compressor);
        $this->assertEquals('WebSocket\Middleware\CompressionExtension\DeflateCompressor', "{$compressor}");
        $middleware = new CompressionExtension($compressor);
        $this->assertInstanceOf(Stringable::class, $middleware);
        $this->assertEquals('WebSocket\Middleware\CompressionExtension', "{$middleware}");
        $connection->addMiddleware($middleware);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET / HTTP/1.1\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Host: test.url\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Extensions: permessage-deflate\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $request = $connection->pullHttp();
        $this->assertEquals(['permessage-deflate'], $request->getHeader('Sec-WebSocket-Extensions'));

        $response = $this->psrFactory->createResponse(200);
        $this->expectSocketStreamWrite()->addAssert(
            function (string $method, array $params): void {
                $this->assertEquals(
                    "HTTP/1.1 200 OK\r\nSec-WebSocket-Extensions: permessage-deflate\r\n\r\n",
                    $params[0]
                );
            }
        );
        $response = $connection->pushHttp($response);
        $this->assertEquals(['permessage-deflate'], $response->getHeader('Sec-WebSocket-Extensions'));
        $this->assertEquals($compressor, $connection->getMeta('compressionExtension.compressor'));
        $this->assertEquals($compressor, $connection->getMeta('compressionExtension.compressor'));
        $this->assertEquals((object)[
            'compressor' => 'permessage-deflate',
            'isServer' => true,
            'serverNoContextTakeover' => false,
            'clientNoContextTakeover' => false,
            'serverMaxWindowBits' => 15,
            'clientMaxWindowBits' => 15,
            'deflator' => null,
            'inflator' => null,
        ], $connection->getMeta('compressionExtension.configuration'));

        // Receive messages
        $this->expectWsReadMessage('wQ8=', 'csssKi5RyE0tLk5MTwUA');
        $message = $connection->pullMessage();
        $this->assertEquals('text', $message->getOpcode());
        $this->assertEquals('First message', $message->getContent());
        $inflator = $connection->getMeta('compressionExtension.configuration')->inflator;

        $this->expectWsReadMessage('wgo=', 'Ck5Nzs9LgfEAAA==');
        $message = $connection->pullMessage();
        $this->assertEquals('binary', $message->getOpcode());
        $this->assertEquals('Second message', $message->getContent());
        $this->assertNull($connection->getMeta('compressionExtension.configuration')->deflator);
        $this->assertNotNull($connection->getMeta('compressionExtension.configuration')->inflator);
        $this->assertSame($inflator, $connection->getMeta('compressionExtension.configuration')->inflator);

        // Send messages
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('wQ9yyywqLlHITS0uTkxPBQA='), $params[0]);
        });
        $connection->text('First message');
        $deflator = $connection->getMeta('compressionExtension.configuration')->deflator;

        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('wgoKTk3Oz0uB8QAA'), $params[0]);
        });
        $connection->binary('Second message');
        $this->assertNotNull($connection->getMeta('compressionExtension.configuration')->deflator);
        $this->assertNotNull($connection->getMeta('compressionExtension.configuration')->inflator);
        $this->assertSame($deflator, $connection->getMeta('compressionExtension.configuration')->deflator);

        $this->expectSocketStreamClose();
        $connection->disconnect();
    }

    // Client request compression, but Server declines - do not use compression
    public function testClientServerDeclines(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = new Connection($stream, false, false);

        $compressor = new DeflateCompressor();
        $this->assertInstanceOf(Stringable::class, $compressor);
        $this->assertEquals('WebSocket\Middleware\CompressionExtension\DeflateCompressor', "{$compressor}");
        $middleware = new CompressionExtension($compressor);
        $this->assertInstanceOf(Stringable::class, $middleware);
        $this->assertEquals('WebSocket\Middleware\CompressionExtension', "{$middleware}");
        $connection->addMiddleware($middleware);

        // Send headers to Server
        $this->expectSocketStreamWrite()->addAssert(
            function (string $method, array $params): void {
                $this->assertEquals(
                    "GET / HTTP/1.1\r\nHost: test.url\r\n"
                    . "Sec-WebSocket-Extensions: permessage-deflate\r\n\r\n",
                    $params[0]
                );
            }
        );
        $request = $this->psrFactory->createRequest('GET', 'ws://test.url');
        $request = $connection->pushHttp($request);
        $this->assertEquals(['permessage-deflate'], $request->getHeader('Sec-WebSocket-Extensions'));

        // Receive heders from Server
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 200 OK\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $response = $connection->pullHttp();
        $this->assertEmpty($response->getHeader('Sec-WebSocket-Extensions'));
        $this->assertNull($connection->getMeta('compressionExtension.compressor'));
        $this->assertNull($connection->getMeta('compressionExtension.configuration'));

        // Send messages
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('gQ1GaXJzdCBtZXNzYWdl'), $params[0]);
        });
        $connection->text('First message');
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('gg5TZWNvbmQgbWVzc2FnZQ=='), $params[0]);
        });
        $connection->binary('Second message');

        // Receive messages
        $this->expectWsReadMessage('gQ0=', 'Rmlyc3QgbWVzc2FnZQ==');
        $message = $connection->pullMessage();
        $this->assertEquals('text', $message->getOpcode());
        $this->assertEquals('First message', $message->getContent());
        $this->expectWsReadMessage('gg4=', 'U2Vjb25kIG1lc3NhZ2U=');
        $message = $connection->pullMessage();
        $this->assertEquals('binary', $message->getOpcode());
        $this->assertEquals('Second message', $message->getContent());

        $this->assertNull($connection->getMeta('compressionExtension.compressor'));
        $this->assertNull($connection->getMeta('compressionExtension.configuration'));

        $this->expectSocketStreamClose();
        $connection->disconnect();
    }

    public function testClientConfiguration(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = new Connection($stream, false, false);

        $compressor = new DeflateCompressor(
            clientNoContextTakeover: true,
            serverNoContextTakeover: true,
            clientMaxWindowBits: 10,
            serverMaxWindowBits: 12,
        );
        $this->assertInstanceOf(Stringable::class, $compressor);
        $this->assertEquals('WebSocket\Middleware\CompressionExtension\DeflateCompressor', "{$compressor}");
        $middleware = new CompressionExtension($compressor);
        $this->assertInstanceOf(Stringable::class, $middleware);
        $this->assertEquals('WebSocket\Middleware\CompressionExtension', "{$middleware}");
        $connection->addMiddleware($middleware);

        // Send headers to Server
        $this->expectSocketStreamWrite()->addAssert(
            function (string $method, array $params): void {
                $this->assertEquals(
                    "GET / HTTP/1.1\r\nHost: test.url\r\n"
                    . "Sec-WebSocket-Extensions: permessage-deflate; server_no_context_takeover; "
                    . "client_no_context_takeover; server_max_window_bits=12; client_max_window_bits=10\r\n\r\n",
                    $params[0]
                );
            }
        );
        $request = $this->psrFactory->createRequest('GET', 'ws://test.url');
        $request = $connection->pushHttp($request);
        $this->assertNull($connection->getMeta('compressionExtension.compressor'));
        $this->assertNull($connection->getMeta('compressionExtension.configuration'));

        // Receive heders from Server
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 200 OK\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Extensions: permessage-deflate\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $response = $connection->pullHttp();
        $this->assertEquals(['permessage-deflate'], $response->getHeader('Sec-WebSocket-Extensions'));
        $this->assertEquals($compressor, $connection->getMeta('compressionExtension.compressor'));
        $this->assertEquals((object)[
            'compressor' => 'permessage-deflate',
            'isServer' => false,
            'serverNoContextTakeover' => true,
            'clientNoContextTakeover' => true,
            'serverMaxWindowBits' => 12,
            'clientMaxWindowBits' => 10,
            'deflator' => null,
            'inflator' => null,
        ], $connection->getMeta('compressionExtension.configuration'));

        // Send messages
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('wQ9yyywqLlHITS0uTkxPBQA='), $params[0]);
        });
        $connection->text('First message');
        $deflator = $this->getConfiguration($connection)->deflator;

        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('whAKTk3Oz0tRyE0tLk5MTwUA'), $params[0]);
        });
        $connection->binary('Second message');
        $this->assertNotNull($this->getConfiguration($connection)->deflator);
        $this->assertNull($this->getConfiguration($connection)->inflator);
        $this->assertNotSame($deflator, $this->getConfiguration($connection)->deflator);

        // Receive messages
        $this->expectWsReadMessage('wQ8=', 'csssKi5RyE0tLk5MTwUA');
        $message = $connection->pullMessage();
        $this->assertEquals('text', $message->getOpcode());
        $this->assertEquals('First message', $message->getContent());
        $inflator = $this->getConfiguration($connection)->inflator;

        $this->expectWsReadMessage('whA=', 'Ck5Nzs9LUchNLS5OTE8FAA==');
        $message = $connection->pullMessage();
        $this->assertEquals('binary', $message->getOpcode());
        $this->assertEquals('Second message', $message->getContent());
        $this->assertNotNull($this->getConfiguration($connection)->deflator);
        $this->assertNotNull($this->getConfiguration($connection)->inflator);
        $this->assertNotSame($inflator, $this->getConfiguration($connection)->inflator);

        $this->expectSocketStreamClose();
        $connection->disconnect();
    }

    public function testServerConfiguration(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = new Connection($stream, false, false);

        $compressor = new DeflateCompressor(
            clientNoContextTakeover: true,
            serverNoContextTakeover: true,
            clientMaxWindowBits: 10,
            serverMaxWindowBits: 12,
        );
        $this->assertInstanceOf(Stringable::class, $compressor);
        $this->assertEquals('WebSocket\Middleware\CompressionExtension\DeflateCompressor', "{$compressor}");
        $middleware = new CompressionExtension($compressor);
        $this->assertInstanceOf(Stringable::class, $middleware);
        $this->assertEquals('WebSocket\Middleware\CompressionExtension', "{$middleware}");
        $connection->addMiddleware($middleware);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET / HTTP/1.1\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Host: test.url\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Extensions: permessage-deflate\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $request = $connection->pullHttp();
        $this->assertEquals(['permessage-deflate'], $request->getHeader('Sec-WebSocket-Extensions'));

        $response = $this->psrFactory->createResponse(200);
        $this->expectSocketStreamWrite()->addAssert(
            function (string $method, array $params): void {
                $this->assertEquals(
                    "HTTP/1.1 200 OK\r\nSec-WebSocket-Extensions: permessage-deflate; server_no_context_takeover; "
                    . "client_no_context_takeover; server_max_window_bits=12; client_max_window_bits=10\r\n\r\n",
                    $params[0]
                );
            }
        );
        $response = $connection->pushHttp($response);
        $this->assertEquals($compressor, $connection->getMeta('compressionExtension.compressor'));
        $this->assertEquals($compressor, $connection->getMeta('compressionExtension.compressor'));
        $this->assertEquals((object)[
            'compressor' => 'permessage-deflate',
            'isServer' => true,
            'serverNoContextTakeover' => true,
            'clientNoContextTakeover' => true,
            'serverMaxWindowBits' => 12,
            'clientMaxWindowBits' => 10,
            'deflator' => null,
            'inflator' => null,
        ], $connection->getMeta('compressionExtension.configuration'));

        // Receive messages
        $this->expectWsReadMessage('wQ8=', 'csssKi5RyE0tLk5MTwUA');
        $message = $connection->pullMessage();
        $this->assertEquals('text', $message->getOpcode());
        $this->assertEquals('First message', $message->getContent());
        $inflator = $connection->getMeta('compressionExtension.configuration')->inflator;

        $this->expectWsReadMessage('whA=', 'Ck5Nzs9LUchNLS5OTE8FAA==');
        $message = $connection->pullMessage();
        $this->assertEquals('binary', $message->getOpcode());
        $this->assertEquals('Second message', $message->getContent());
        $this->assertNull($connection->getMeta('compressionExtension.configuration')->deflator);
        $this->assertNotNull($connection->getMeta('compressionExtension.configuration')->inflator);
        $this->assertNotSame($inflator, $connection->getMeta('compressionExtension.configuration')->inflator);

        // Send messages
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('wQ9yyywqLlHITS0uTkxPBQA='), $params[0]);
        });
        $connection->text('First message');
        $deflator = $connection->getMeta('compressionExtension.configuration')->deflator;

        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('whAKTk3Oz0tRyE0tLk5MTwUA'), $params[0]);
        });
        $connection->binary('Second message');
        $this->assertNotNull($connection->getMeta('compressionExtension.configuration')->deflator);
        $this->assertNotNull($connection->getMeta('compressionExtension.configuration')->inflator);
        $this->assertNotSame($deflator, $connection->getMeta('compressionExtension.configuration')->deflator);

        $this->expectSocketStreamClose();
        $connection->disconnect();
    }

    public function testClientConfigurationByServer(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = new Connection($stream, false, false);

        $compressor = new DeflateCompressor(
            clientMaxWindowBits: 10,
            serverMaxWindowBits: 12,
        );
        $this->assertInstanceOf(Stringable::class, $compressor);
        $this->assertEquals('WebSocket\Middleware\CompressionExtension\DeflateCompressor', "{$compressor}");
        $middleware = new CompressionExtension($compressor);
        $this->assertInstanceOf(Stringable::class, $middleware);
        $this->assertEquals('WebSocket\Middleware\CompressionExtension', "{$middleware}");
        $connection->addMiddleware($middleware);

        // Send headers to Server
        $this->expectSocketStreamWrite()->addAssert(
            function (string $method, array $params): void {
                $this->assertEquals(
                    "GET / HTTP/1.1\r\nHost: test.url\r\n"
                    . "Sec-WebSocket-Extensions: permessage-deflate; server_max_window_bits=12; "
                    . "client_max_window_bits=10\r\n\r\n",
                    $params[0]
                );
            }
        );
        $request = $this->psrFactory->createRequest('GET', 'ws://test.url');
        $request = $connection->pushHttp($request);

        // Receive heders from Server
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "HTTP/1.1 200 OK\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Extensions: permessage-deflate; server_no_context_takeover; "
             . "client_no_context_takeover; server_max_window_bits=11; client_max_window_bits=9\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $response = $connection->pullHttp();
        $this->assertEquals($compressor, $connection->getMeta('compressionExtension.compressor'));
        $this->assertEquals((object)[
            'compressor' => 'permessage-deflate',
            'isServer' => false,
            'serverNoContextTakeover' => true,
            'clientNoContextTakeover' => true,
            'serverMaxWindowBits' => 11,
            'clientMaxWindowBits' => 9,
            'deflator' => null,
            'inflator' => null,
        ], $connection->getMeta('compressionExtension.configuration'));

        $this->expectSocketStreamClose();
        $connection->disconnect();
    }

    public function testServerConfigurationByServer(): void
    {
        $temp = tmpfile();

        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectContext();
        $stream = new SocketStream($temp);

        $this->expectSocketStreamGetLocalName();
        $this->expectSocketStreamGetRemoteName();
        $this->expectSocketStreamSetTimeout();
        $connection = new Connection($stream, false, false);

        $compressor = new DeflateCompressor(
            clientNoContextTakeover: true,
            serverNoContextTakeover: true,
            clientMaxWindowBits: 9,
            serverMaxWindowBits: 11,
        );
        $this->assertInstanceOf(Stringable::class, $compressor);
        $this->assertEquals('WebSocket\Middleware\CompressionExtension\DeflateCompressor', "{$compressor}");
        $middleware = new CompressionExtension($compressor);
        $this->assertInstanceOf(Stringable::class, $middleware);
        $this->assertEquals('WebSocket\Middleware\CompressionExtension', "{$middleware}");
        $connection->addMiddleware($middleware);

        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "GET / HTTP/1.1\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Host: test.url\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "Sec-WebSocket-Extensions: permessage-deflate; server_max_window_bits=12; "
                . "client_max_window_bits=10\r\n";
        });
        $this->expectSocketStreamReadLine()->setReturn(function () {
            return "\r\n";
        });
        $request = $connection->pullHttp();

        $response = $this->psrFactory->createResponse(200);
        $this->expectSocketStreamWrite()->addAssert(
            function (string $method, array $params): void {
                $this->assertEquals(
                    "HTTP/1.1 200 OK\r\nSec-WebSocket-Extensions: permessage-deflate; server_no_context_takeover; "
                    . "client_no_context_takeover; server_max_window_bits=11; client_max_window_bits=9\r\n\r\n",
                    $params[0]
                );
            }
        );
        $response = $connection->pushHttp($response);
        $this->assertEquals($compressor, $connection->getMeta('compressionExtension.compressor'));
        $this->assertEquals($compressor, $connection->getMeta('compressionExtension.compressor'));
        $this->assertEquals((object)[
            'compressor' => 'permessage-deflate',
            'isServer' => true,
            'serverNoContextTakeover' => true,
            'clientNoContextTakeover' => true,
            'serverMaxWindowBits' => 11,
            'clientMaxWindowBits' => 9,
            'deflator' => null,
            'inflator' => null,
        ], $connection->getMeta('compressionExtension.configuration'));

        // Receive messages
        $this->expectWsReadMessage('wQ8=', 'csssKi5RyE0tLk5MTwUA');
        $message = $connection->pullMessage();
        $this->assertEquals('text', $message->getOpcode());
        $this->assertEquals('First message', $message->getContent());
        $inflator = $connection->getMeta('compressionExtension.configuration')->inflator;

        $this->expectWsReadMessage('whA=', 'Ck5Nzs9LUchNLS5OTE8FAA==');
        $message = $connection->pullMessage();
        $this->assertEquals('binary', $message->getOpcode());
        $this->assertEquals('Second message', $message->getContent());
        $this->assertNull($connection->getMeta('compressionExtension.configuration')->deflator);
        $this->assertNotNull($connection->getMeta('compressionExtension.configuration')->inflator);
        $this->assertNotSame($inflator, $connection->getMeta('compressionExtension.configuration')->inflator);

        // Send messages
        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('wQ9yyywqLlHITS0uTkxPBQA='), $params[0]);
        });
        $connection->text('First message');
        $deflator = $connection->getMeta('compressionExtension.configuration')->deflator;

        $this->expectSocketStreamWrite()->addAssert(function ($method, $params) {
            $this->assertEquals(base64_decode('whAKTk3Oz0tRyE0tLk5MTwUA'), $params[0]);
        });
        $connection->binary('Second message');
        $this->assertNotNull($connection->getMeta('compressionExtension.configuration')->deflator);
        $this->assertNotNull($connection->getMeta('compressionExtension.configuration')->inflator);
        $this->assertNotSame($deflator, $connection->getMeta('compressionExtension.configuration')->deflator);

        $this->expectSocketStreamClose();
        $connection->disconnect();
    }

    public function testDeflateCompressorNoExtension(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DeflateCompressor require fake extension.');
        new DeflateCompressor(extension: 'fake');
    }

    public function testDeflateCompressorServerBitsToLow(): void
    {
        $this->expectException(RangeException::class);
        $this->expectExceptionMessage('serverMaxWindowBits must be in range 9-15.');
        new DeflateCompressor(serverMaxWindowBits: 8);
    }

    public function testDeflateCompressorServerBitsToHigh(): void
    {
        $this->expectException(RangeException::class);
        $this->expectExceptionMessage('serverMaxWindowBits must be in range 9-15.');
        new DeflateCompressor(serverMaxWindowBits: 16);
    }

    public function testDeflateCompressorClientBitsToLow(): void
    {
        $this->expectException(RangeException::class);
        $this->expectExceptionMessage('clientMaxWindowBits must be in range 9-15.');
        new DeflateCompressor(clientMaxWindowBits: 8);
    }

    public function testDeflateCompressorClientBitsToHigh(): void
    {
        $this->expectException(RangeException::class);
        $this->expectExceptionMessage('clientMaxWindowBits must be in range 9-15.');
        new DeflateCompressor(clientMaxWindowBits: 16);
    }

    public function testHeaderParsing(): void
    {
        $compressor = new DeflateCompressor();

        $header = 'client_max_window_bits=10;server_max_window_bits=11;';
        $configuration = $compressor->getConfiguration($header, false);
        $this->assertSame(10, $configuration->clientMaxWindowBits);
        $this->assertSame(11, $configuration->serverMaxWindowBits);

        $header = ' client_max_window_bits = "10" ; server_max_window_bits=\'11\' ; ';
        $configuration = $compressor->getConfiguration($header, false);
        $this->assertSame(10, $configuration->clientMaxWindowBits);
        $this->assertSame(11, $configuration->serverMaxWindowBits);

        $header = 'client_max_window_bits; server_max_window_bits=invalid;';
        $configuration = $compressor->getConfiguration($header, false);
        $this->assertSame(15, $configuration->clientMaxWindowBits);
        $this->assertSame(15, $configuration->serverMaxWindowBits);

        $header = 'client_max_window_bits=8;server_max_window_bits=16;';
        $configuration = $compressor->getConfiguration($header, false);
        $this->assertSame(9, $configuration->clientMaxWindowBits);
        $this->assertSame(15, $configuration->serverMaxWindowBits);
    }


    /**
     * PhpStan cannot resolve otherwise
     * @return Config
     */
    private function getConfiguration(Connection $connection): object
    {
        return $connection->getMeta('compressionExtension.configuration');
    }
}
