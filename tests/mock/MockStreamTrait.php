<?php

/**
 * This class is used by phpunit tests to mock and track various socket/stream calls.
 */

namespace WebSocket;

use Phrity\Net\Mock\Mock;
use Throwable;

trait MockStreamTrait
{
    private $stack = [];
    private $last_ws_key;


    /* ---------- Setup methods ------------------------------------------------------------------------------------ */

    private function setUpStack(): void
    {
        $this->stack = [];
//        Mock::setLogger(new \Phrity\Net\Mock\EchoLogger());
        Mock::setCallback(function ($counter, $method, $params, $default) {
//echo " >>> $counter, $method \n";
            $assert = array_shift($this->stack);
            if ($assert) {
                return $assert($method, $params, $default);
            }
            $this->fail("Unexpected {$method} on index {$counter}.");
        });
    }

    private function tearDownStack(): void
    {
        if (!empty($this->stack)) {
            $count = count($this->stack);
            $this->fail("Expected {$count} more asserts on stack.");
        }
    }


    /* ---------- Atomic asserts ----------------------------------------------------------------------------------- */

    private function expectStreamClose(): void
    {
        $this->stack[] = function (string $method, array $params, callable $default): void {
            $this->assertEquals('SocketStream.close', $method);
            $this->assertCount(0, $params);
            $default($params);
        };
    }

    private function expectStreamFactory(): void
    {
        $this->stack[] = function (string $method, array $params, callable $default): void {
            $this->assertEquals('StreamFactory.__construct', $method);
            $this->assertCount(0, $params);
            $default($params);
        };
    }

    private function expectStreamMetadata(?array $return = null): void
    {
        $this->stack[] = function (string $method, array $params, callable $default) use ($return): ?array {
            $this->assertEquals('SocketStream.getMetadata', $method);
            $this->assertCount(0, $params);
            return is_null($return) ? $default($params) : $return;
        };
    }

    private function expectStreamRead(?int $expect = null, $return): void
    {
        $this->stack[] = function (string $method, array $params, callable $default) use ($expect, $return): string {
            $this->assertEquals('SocketStream.read', $method);
            $this->assertCount(1, $params);
            if (!is_null($expect)) {
                $this->assertEquals($expect, $params[0]);
            }
            switch (gettype($return)) {
                case 'array':
                    return is_null($return) ? '' : array_reduce($return, function ($carry, $item) {
                        return $carry . chr($item);
                    }, '');
                case 'string':
                    return $return;
                case 'object':
                    throw $return;
            }
        };
    }

    private function expectStreamReadLine(?int $expect = null, $return = null): void
    {
        $this->stack[] = function (string $method, array $params, callable $default) use ($expect, $return): string {
            $this->assertEquals('SocketStream.readLine', $method);
            $this->assertCount(1, $params);
            if (!is_null($expect)) {
                $this->assertEquals($expect, $params[0]);
            }
            switch (gettype($return)) {
                case 'array':
                    return is_null($return) ? '' : array_reduce($return, function ($carry, $item) {
                        return $carry . chr($item);
                    }, '');
                case 'string':
                    return $return;
                case 'object':
                    throw $return;
            }
        };
    }

    private function expectStreamWrite($expect = null, $return = null): void
    {
        $this->stack[] = function (string $method, array $params, callable $default) use ($expect, $return): int {
            $this->assertEquals('SocketStream.write', $method);
            $this->assertCount(1, $params);
            switch (gettype($expect)) {
                case 'array':
                    $this->assertEquals(count($expect), strlen($params[0]));
                    $expect_relevant = array_filter($expect, function ($item) {
                        return !is_null($item);
                    });
                    $input_relevant = array_intersect_key(array_map('ord', str_split($params[0])), $expect_relevant);
                    $this->assertEquals($expect_relevant, $input_relevant);
                    break;
                case 'int':
                    $this->assertEquals($expect, strlen($params[0]));
                    break;
                case 'string':
                    $this->assertEquals($expect, $params[0]);
                    break;
            }
            switch (gettype($return)) {
                case 'object':
                    throw $return;
            }
            return is_null($return) ? strlen($params[0]) : $return;
        };
    }

    private function expectStreamTimeout(?int $expect = null, ?int $return = null): void
    {
        $this->stack[] = function (string $method, array $params, callable $default) use ($expect): bool {
            $this->assertEquals('SocketStream.setTimeout', $method);
            $this->assertEquals($expect, $params[0]);
            return $default($params);
        };
    }

    private function expectStreamTell(?int $expect = null, $return = null): void
    {
        $this->stack[] = function (string $method, array $params, callable $default) use ($expect, $return) {
            $this->assertEquals('SocketStream.tell', $method);
            $this->assertCount(0, $params);
            return is_null($return) ? $default($params) : $return;
        };
    }

    private function expectClientConnect(?Throwable $exception = null): void
    {
        $this->stack[] = function (string $method, array $params, callable $default) use ($exception): object {
            $this->assertEquals('SocketClient.connect', $method);
            $this->assertCount(0, $params);
            if ($exception) {
                throw $exception;
            }
            return $default($params);
        };
    }

    private function expectStreamConstruct($return = null): void
    {
        $this->stack[] = function (string $method, array $params, callable $default) use ($return): ?string {
            $this->assertEquals('SocketStream.__construct', $method);
            return is_null($return) ? $default($params) : $return;
        };
    }

    private function expectStreamLocalName($return = null): void
    {
        $this->stack[] = function (string $method, array $params, callable $default) use ($return): ?string {
            $this->assertEquals('SocketStream.getLocalName', $method);
            return is_null($return) ? $default($params) : $return;
        };
    }

    private function expectStreamRemoteName($return = null): void
    {
        $this->stack[] = function (string $method, array $params, callable $default) use ($return): ?string {
            $this->assertEquals('SocketStream.getRemoteName', $method);
            return is_null($return) ? $default($params) : $return;
        };
    }

    private function expectStreamResourceType($return = null): void
    {
        $this->stack[] = function (string $method, array $params, callable $default) use ($return): ?string {
            $this->assertEquals('SocketStream.getResourceType', $method);
            return is_null($return) ? $default($params) : $return;
        };
    }

    private function expectServerAccept($return = null): void
    {
        $this->stack[] = function (string $method, array $params, callable $default) use ($return): object {
            $this->assertEquals('SocketServer.accept', $method);
            return $default($params);
        };
    }

    private function expectFactoryCreateSockerServer(int $port = 8000): void
    {
        $this->stack[] = function (string $method, array $params, callable $default) use ($port): object {
            $this->assertEquals('StreamFactory.createSocketServer', $method);
            $this->assertCount(1, $params);
            $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
            $this->assertEquals("tcp://0.0.0.0:{$port}", "{$params[0]}");
            return $default($params);
        };
    }

    private function expectSockerServerConstruct(int $port = 8000, $return = null): void
    {
        $this->stack[] = function (string $method, array $params, callable $default) use ($port, $return): void {
            $this->assertEquals('SocketServer.__construct', $method);
            $this->assertCount(1, $params);
            $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
            $this->assertEquals("tcp://0.0.0.0:{$port}", "{$params[0]}");
            switch (gettype($return)) {
                case 'object':
                    throw $return;
            }
            $default($params);
        };
    }

    /* ---------- Combined asserts --------------------------------------------------------------------------------- */

    private function expectSocketServer(): void
    {
        $this->expectFactoryCreateSockerServer();
        $this->expectSockerServerConstruct();
        $this->stack[] = function (string $method, array $params, callable $default): array {
            $this->assertEquals('SocketServer.getTransports', $method);
            $this->assertCount(0, $params);
            return ['tcp', 'ssl'];
        };
    }


    private function expectSocketClient(
        string $scheme = 'tcp',
        string $host = 'localhost:8000',
        int $timeout = 5,
        bool $persistent = false,
    ): void {
        $this->stack[] = function (string $method, array $params, callable $default) use ($scheme, $host): object {
            $this->assertEquals('StreamFactory.createSocketClient', $method);
            $this->assertCount(1, $params);
            $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
            $this->assertEquals("{$scheme}://{$host}", "{$params[0]}");
            return $default($params);
        };
        $this->stack[] = function (string $method, array $params, callable $default) use ($scheme, $host): void {
            $this->assertEquals('SocketClient.__construct', $method);
            $this->assertCount(1, $params);
            $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
            $this->assertEquals("{$scheme}://{$host}", "{$params[0]}");
            $default($params);
        };
        $this->stack[] = function (string $method, array $params, callable $default) use ($persistent): object {
            $this->assertEquals('SocketClient.setPersistent', $method);
            $this->assertCount(1, $params);
            $this->assertEquals($persistent, $params[0]);
            return $default($params);
        };
        $this->stack[] = function (string $method, array $params, callable $default) use ($timeout): object {
            $this->assertEquals('SocketClient.setTimeout', $method);
            $this->assertCount(1, $params);
            $this->assertEquals($timeout, $params[0]);
            return $default($params);
        };
        $this->stack[] = function (string $method, array $params, callable $default): object {
            $this->assertEquals('SocketClient.setContext', $method);
            $this->assertCount(1, $params);
            return $default($params);
        };
    }

    private function expectClientHandshake(
        string $host = 'localhost:8000',
        string $path = '/my/mock/path',
        string $headers = '',
        int $timeout = 5,
        $tell = null,
    ): void {
        $this->expectClientConnect();
        $this->expectStreamConstruct();
        $this->expectStreamMetadata();
        $this->expectStreamResourceType();
        if (!is_null($tell)) {
            $this->expectStreamTell(null, false);
        }
        $this->expectStreamTimeout($timeout);
        $this->stack[] = function (string $method, array $params, callable $default) use ($host, $path, $headers): int {
            $this->assertEquals('SocketStream.write', $method);
            preg_match('/Sec-WebSocket-Key: ([\S]*)\r\n/', $params[0], $m);
            $this->last_ws_key = $m[1];
            $this->assertEquals(
                "GET {$path} HTTP/1.1\r\nHost: {$host}\r\nUser-Agent: websocket-client-php\r\nConnection: Upgrade"
                . "\r\nUpgrade: websocket\r\nSec-WebSocket-Key: {$this->last_ws_key}\r\nSec-WebSocket-Version: 13"
                . "\r\n{$headers}\r\n",
                $params[0]
            );
            return strlen($params[0]);
        };
        $this->stack[] = function (string $method, array $params, callable $default): string {
            $this->assertEquals('SocketStream.readLine', $method);
            $this->assertEquals(1024, $params[0]);
            $ws_key_res = base64_encode(pack('H*', sha1($this->last_ws_key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
            return "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade"
            . "\r\nSec-WebSocket-Accept: {$ws_key_res}\r\n\r\n";
        };
    }

    private function expectServerHandshake(?int $timeout = null)
    {
        $this->expectServerAccept();
        $this->expectStreamConstruct();
        $this->expectStreamMetadata();
        if (!is_null($timeout)) {
            $this->expectStreamTimeout($timeout);
        }
        $this->expectStreamRemoteName('localhost:8000');
        $this->expectStreamReadLine(1024, "GET /my/mock/path HTTP/1.1\r\nHost: localhost:8000\r\nUser-Agent: websocket-client-php\r\nConnection: Upgrade"
                . "\r\nUpgrade: websocket\r\nSec-WebSocket-Key: cktLWXhUdDQ2OXF0ZCFqOQ==\r\nSec-WebSocket-Version: 13"
                . "\r\n\r\n");
        $this->expectStreamWrite("HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: YmysboNHNoWzWVeQpduY7xELjgU=\r\n\r\n", 129);
    }

    private function expectStreamDestruct(): void
    {
        $this->expectStreamClose();
        $this->expectStreamMetadata();
    }

    private function expectCreate(): void
    {
        Mock::setCallback(function ($counter, $method, $params, $default) {
            switch ($counter) {
                case 0:
                    $this->assertEquals('StreamFactory.__construct', $method);
                    return $default($params);
                default:
                    $this->fail("Unexpected {$method} on index {$counter}.");
            }
        });
    }

    private function expectHandshake(
        string $scheme = 'tcp',
        string $host = 'localhost:8000',
        string $path = '/my/mock/path',
        string $headers = '',
        int $timeout = 5
    ): void {
        Mock::setCallback(function (
            $counter,
            $method,
            $params,
            $default
        ) use (
            $scheme,
            $host,
            $path,
            $headers,
            $timeout
        ) {
            switch ($counter) {
                case 0:
                    $this->assertEquals('StreamFactory.createSocketClient', $method);
                    $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
                    $this->assertEquals("{$scheme}://{$host}", "{$params[0]}");
                    return $default($params);
                case 1:
                    $this->assertEquals('SocketClient.__construct', $method);
                    $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
                    $this->assertEquals("{$scheme}://{$host}", "{$params[0]}");
                    return $default($params);
                case 2:
                    $this->assertEquals('SocketClient.setPersistent', $method);
                    $this->assertFalse($params[0]);
                    return $default($params);
                case 3:
                    $this->assertEquals('SocketClient.setTimeout', $method);
                    $this->assertEquals($timeout, $params[0]);
                    return $default($params);
                case 4:
                    $this->assertEquals('SocketClient.setContext', $method);
                    return $default($params);
                case 5:
                    $this->assertEquals('SocketClient.connect', $method);
                    return $default($params);
                case 6:
                    $this->assertEquals('SocketStream.__construct', $method);
                    return $default($params);
                case 7:
                    $this->assertEquals('SocketStream.getMetadata', $method);
                    return $default($params);
                case 8:
                    $this->assertEquals('SocketStream.setTimeout', $method);
                    $this->assertEquals($timeout, $params[0]);
                    return $default($params);
                case 9:
                    $this->assertEquals('SocketStream.write', $method);
                    preg_match('/Sec-WebSocket-Key: ([\S]*)\r\n/', $params[0], $m);
                    $this->last_ws_key = $m[1];
                    $this->assertEquals(
                        "GET {$path} HTTP/1.1\r\nHost: {$host}\r\nUser-Agent: websocket-client-php\r\n"
                        . "Connection: Upgrade\r\nUpgrade: websocket\r\nSec-WebSocket-Key: {$this->last_ws_key}\r\n"
                        . "Sec-WebSocket-Version: 13\r\n{$headers}\r\n",
                        $params[0]
                    );
                    return strlen($params[0]);
                case 10:
                    $this->assertEquals('SocketStream.readLine', $method);
                    $this->assertEquals(1024, $params[0]);
                    $ws_key_res = base64_encode(
                        pack('H*', sha1($this->last_ws_key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11'))
                    );
                    return "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\n"
                        . "Connection: Upgrade\r\nSec-WebSocket-Accept: {$ws_key_res}\r\n\r\n";
                case 11:
                    $this->assertEquals('SocketStream.write', $method);
                    $this->assertEquals(13, strlen($params[0]));
                    return strlen($params[0]);
                default:
                    $this->fail("Unexpected {$method} on index {$counter}.");
            }
        });
    }
}
