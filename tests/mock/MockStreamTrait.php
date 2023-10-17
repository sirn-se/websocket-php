<?php

/**
 * This class is used by phpunit tests to mock and track various socket/stream calls.
 */

namespace WebSocket\Test;

use Phrity\Net\Mock\StreamCollection;
use Phrity\Net\Mock\Stack\StackItem;

trait MockStreamTrait
{
    private $stack = [];
    private $last_ws_key;


    /* ---------- WebSocket Client combinded asserts --------------------------------------------------------------- */

    private function expectWsClientConnect(
        string $scheme = 'tcp',
        string $host = 'localhost',
        int $port = 8000,
        int $timeout = 60,
        array $context = [],
        bool $persistent = false
    ): void {

        $this->expectStreamFactoryCreateStreamCollection();
        $this->expectStreamCollection();
        $this->expectStreamFactoryCreateSocketClient()->addAssert(
            function ($method, $params) use ($scheme, $host, $port) {
                $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
                $this->assertEquals("{$scheme}://{$host}:{$port}", "{$params[0]}");
            }
        );
        $this->expectSocketClient()->addAssert(function ($method, $params) use ($scheme, $host, $port) {
            $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
            $this->assertEquals("{$scheme}://{$host}:{$port}", "{$params[0]}");
        });
        $this->expectSocketClientSetPersistent()->addAssert(function ($method, $params) use ($persistent) {
            $this->assertEquals($persistent, $params[0]);
        });
        $this->expectSocketClientSetTimeout()->addAssert(function ($method, $params) use ($timeout) {
            $this->assertEquals($timeout, $params[0]);
        });
        $this->expectSocketClientSetContext()->addAssert(function ($method, $params) use ($context) {
            $this->assertEquals($context, $params[0]);
        });
        $this->expectSocketClientConnect();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectSocketStreamGetRemoteName()->setReturn(function () use ($host, $port) {
            return "{$host}:{$port}";
        });
        $this->expectStreamCollectionAttach();
        $this->expectSocketStreamGetLocalName()->setReturn(function () {
            return "127.0.0.1:12345";
        });
        $this->expectSocketStreamGetRemoteName()->setReturn(function () use ($host, $port) {
            return "{$host}:{$port}";
        });

        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) use ($timeout) {
            $this->assertEquals($timeout, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        if ($persistent) {
            $this->expectSocketStreamTell();
        }
    }

    private function expectWsClientPerformHandshake(
        string $host = 'localhost:8000',
        string $path = '/my/mock/path',
        string $headers = ''
    ): void {
        $this->expectSocketStreamWrite()->addAssert(
            function (string $method, array $params) use ($host, $path, $headers): void {
                preg_match('/Sec-WebSocket-Key: ([\S]*)\r\n/', $params[0], $m);
                $this->last_ws_key = $m[1];
                $this->assertEquals(
                    "GET {$path} HTTP/1.1\r\nHost: {$host}\r\nUser-Agent: websocket-client-php\r\nConnection: Upgrade"
                    . "\r\nUpgrade: websocket\r\nSec-WebSocket-Key: {$this->last_ws_key}\r\nSec-WebSocket-Version: 13"
                    . "\r\n{$headers}\r\n",
                    $params[0]
                );
            }
        );
        $this->expectSocketStreamReadLine()->addAssert(function (string $method, array $params): void {
            $this->assertEquals(1024, $params[0]);
        })->setReturn(function (array $params) {
            $ws_key_res = base64_encode(pack('H*', sha1($this->last_ws_key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
            return "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade"
            . "\r\nSec-WebSocket-Accept: {$ws_key_res}\r\n\r\n";
        });
    }


    /* ---------- WebSocket Server combinded asserts --------------------------------------------------------------- */

    private function expectWsServerSetup(string $scheme = 'tcp', int $port = 8000): void
    {
        $this->expectStreamFactoryCreateSocketServer()->addAssert(function ($method, $params) use ($scheme, $port) {
            $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
            $this->assertEquals("{$scheme}://0.0.0.0:{$port}", "{$params[0]}");
        });
        $this->expectSocketServer()->addAssert(function ($method, $params) use ($scheme, $port) {
            $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
            $this->assertEquals("{$scheme}://0.0.0.0:{$port}", "{$params[0]}");
        });
        $this->expectSocketServerGetTransports();
        $this->expectSocketServerGetMetadata();
        $this->expectStreamFactoryCreateStreamCollection();
        $this->expectStreamCollection();
        $this->expectStreamCollectionAttach()->addAssert(function ($method, $params) {
            $this->assertEquals('@server', $params[1]);
        });
    }

    private function expectWsSelectConnections(array $keys = []): StackItem
    {
        $this->expectStreamCollectionWaitRead()->setReturn(function ($params, $default, $collection) use ($keys) {
            $keys = array_flip($keys);
            $selected = new StreamCollection();
            foreach ($collection as $key => $stream) {
                if (array_key_exists($key, $keys)) {
                    $selected->attach($stream, $key);
                }
            }
            return $selected;
        });
        $last = $this->expectStreamCollection();
        foreach ($keys as $key) {
            $last = $this->expectStreamCollectionAttach();
        }
        return $last;
    }

    private function expectWsServerPerformHandshake(
        string $host = 'localhost:8000',
        string $path = '/my/mock/path',
        string $headers = ''
    ): void {
        $this->expectSocketStreamReadLine()->addAssert(function (string $method, array $params): void {
            $this->assertEquals(1024, $params[0]);
        })->setReturn(function (array $params) use ($host, $path, $headers) {
            return "GET {$path} HTTP/1.1\r\nHost: {$host}\r\nUser-Agent: websocket-client-php\r\n"
            . "Connection: Upgrade\r\nUpgrade: websocket\r\nSec-WebSocket-Key: cktLWXhUdDQ2OXF0ZCFqOQ==\r\n"
            . "Sec-WebSocket-Version: 13"
            . "\r\n{$headers}\r\n";
        });
        $this->expectSocketStreamWrite()->addAssert(function (string $method, array $params): void {
            $expect = "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\n"
            . "Sec-WebSocket-Accept: YmysboNHNoWzWVeQpduY7xELjgU=\r\n\r\n";
            $this->assertEquals($expect, $params[0]);
        })->setReturn(function () {
            return 129;
        });
    }
}
