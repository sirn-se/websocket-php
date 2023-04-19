<?php

/**
 * Test case for Server.
 * Note that this test is performed by mocking socket/stream calls.
 */

declare(strict_types=1);

namespace WebSocket;

use ErrorException;
use Phrity\Net\Mock\Mock;
use Phrity\Net\Uri;
use Phrity\Util\ErrorHandler;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ServerTest extends TestCase
{
    use MockStreamTrait;

    public function setUp(): void
    {
        error_reporting(-1);
        $this->setUpStack();
    }

    public function tearDown(): void
    {
        $this->tearDownStack();
    }

    public function testServerMasked(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->assertFalse($server->isConnected());
        $this->assertEquals(null, $server->getLastOpcode());
        $this->assertEquals(4096, $server->getFragmentSize());

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerHandshake();
        $this->expectStreamWrite(null, 9);
        $server->send('Connect');

        $this->assertEquals(8000, $server->getPort());
        $this->assertEquals('/my/mock/path', $server->getPath());

        $this->expectStreamResourceType();
        $this->assertTrue($server->isConnected());
        $this->assertEquals(4096, $server->getFragmentSize());
        $this->assertNull($server->getCloseStatus());
        $this->assertEquals([
            'GET /my/mock/path HTTP/1.1',
            'Host: localhost:8000',
            'User-Agent: websocket-client-php',
            'Connection: Upgrade',
            'Upgrade: websocket',
            'Sec-WebSocket-Key: cktLWXhUdDQ2OXF0ZCFqOQ==',
            'Sec-WebSocket-Version: 13',
        ], $server->getRequest());
        $this->assertEquals('websocket-client-php', $server->getHeader('USER-AGENT'));
        $this->assertNull($server->getHeader('no such header'));

        $this->expectStreamResourceType();
        $this->expectStreamWrite(null, 19);
        $server->send('Sending a message');

        $this->expectStreamResourceType();
        $this->expectStreamRead(2, [129, 147]);
        $this->expectStreamRead(4, [33, 111, 149, 174]);
        $this->expectStreamRead(
            19,
            [115, 10, 246, 203, 72, 25, 252, 192, 70, 79, 244, 142, 76, 10, 230, 221, 64, 8, 240]
        );
        $message = $server->receive();
        $this->assertEquals('Receiving a message', $message);
        $this->assertNull($server->getCloseStatus());
        $this->assertEquals('text', $server->getLastOpcode());

        $this->expectStreamResourceType();
        $this->expectStreamWrite(null, 8);
        $this->expectStreamRead(2, [136, 154]);
        $this->expectStreamRead(4, [245, 55, 62, 8]);
        $this->expectStreamRead(
            26,
            [246, 223, 125, 100, 154, 68, 91, 40, 148, 84, 85, 102, 154, 64, 82, 109, 145, 80, 91, 108, 207,
            23, 15, 56, 197, 7]
        );
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectStreamResourceType();

        $server->close();

        $this->expectStreamResourceType();
        $this->assertFalse($server->isConnected());
        $this->assertEquals(1000, $server->getCloseStatus());

        $this->expectStreamResourceType();
        $server->close(); // Already closed

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testDestruct(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerHandshake();
        $this->expectStreamRead(2, [129, 147]);
        $this->expectStreamRead(4, [33, 111, 149, 174]);
        $this->expectStreamRead(
            19,
            [115, 10, 246, 203, 72, 25, 252, 192, 70, 79, 244, 142, 76, 10, 230, 221, 64, 8, 240]
        );
        $message = $server->receive();

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testServerWithTimeout(): void
    {
        $this->expectStreamFactory();
        $server = new Server(['timeout' => 300]);
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerHandshake(timeout: 300);
        $this->expectStreamWrite(null, 9);
        $server->send('Connect');

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testPayload128(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());
        $server->setFragmentSize(65540);

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerHandshake();
        $this->expectStreamWrite(null, 9);
        $server->send('Connect');

        $payload = file_get_contents(__DIR__ . '/mock/payload.128.txt');

        $this->expectStreamResourceType();
        $this->expectStreamWrite(136);
        $server->send($payload, 'text');

        $this->expectStreamResourceType();
        $this->expectStreamRead(2, [129, 126]);
        $this->expectStreamRead(2, [0, 128]);
        $this->expectStreamRead(128, substr($payload, 0, 132));

        $message = $server->receive();
        $this->assertEquals($payload, $message);

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testPayload65536(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());
        $server->setFragmentSize(65540);

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerHandshake();
        $this->expectStreamWrite(null, 9);
        $server->send('Connect');

        $payload = file_get_contents(__DIR__ . '/mock/payload.65536.txt');

        $this->expectStreamResourceType();
        $this->expectStreamWrite(65550);
        $server->send($payload, 'text');

        $this->expectStreamResourceType();
        $this->expectStreamRead(2, [129, 127]);
        $this->expectStreamRead(8, [0, 0, 0, 0, 0, 1, 0, 0]);
        $this->expectStreamRead(65536, substr($payload, 0, 16374));
        $this->expectStreamRead(49162, substr($payload, 16374, 8192));
        $this->expectStreamRead(40970, substr($payload, 24566, 8192));
        $this->expectStreamRead(32778, substr($payload, 32758, 8192));
        $this->expectStreamRead(24586, substr($payload, 40950, 8192));
        $this->expectStreamRead(16394, substr($payload, 49142, 8192));
        $this->expectStreamRead(8202, substr($payload, 57334, 8192));
        $this->expectStreamRead(10, substr($payload, 65526, 10));

        $message = $server->receive();
        $this->assertEquals($payload, $message);

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testMultiFragment(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerHandshake();
        $this->expectStreamWrite(null, 9);
        $server->send('Connect');

        $this->expectStreamResourceType();
        $this->expectStreamWrite(10);
        $this->expectStreamWrite(10);
        $this->expectStreamWrite(5);
        $server->setFragmentSize(8);
        $server->send('Multi fragment test');

        $this->expectStreamResourceType();
        $this->expectStreamRead(2, [1, 136]);
        $this->expectStreamRead(4, [105, 29, 187, 18]);
        $this->expectStreamRead(8, [36, 104, 215, 102, 0, 61, 221, 96]);
        $this->expectStreamRead(2, [0, 136]);
        $this->expectStreamRead(4, [221, 240, 46, 69]);
        $this->expectStreamRead(8, [188, 151, 67, 32, 179, 132, 14, 49]);
        $this->expectStreamRead(2, [128, 131]);
        $this->expectStreamRead(4, [9, 60, 117, 193]);
        $this->expectStreamRead(3, [108, 79, 1]);
        $message = $server->receive();
        $this->assertEquals('Multi fragment test', $message);

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testPingPong(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerHandshake();
        $this->expectStreamWrite(null, 9);
        $server->send('Connect');

        $this->expectStreamResourceType();
        $this->expectStreamWrite(13);
        $server->send('Server ping', 'ping');

        $this->expectStreamResourceType();
        $this->expectStreamWrite(2);
        $server->send('', 'ping');

        $this->expectStreamResourceType();
        $this->expectStreamRead(2, [138, 139]);
        $this->expectStreamRead(4, [1, 1, 1, 1]);
        $this->expectStreamRead(11, [82, 100, 115, 119, 100, 115, 33, 113, 104, 111, 102]);
        $this->expectStreamRead(2, [138, 128]);
        $this->expectStreamRead(4, [1, 1, 1, 1]);
        $this->expectStreamRead(2, [137, 139]);
        $this->expectStreamRead(4, [180, 77, 192, 201]);
        $this->expectStreamRead(11, [247, 33, 169, 172, 218, 57, 224, 185, 221, 35, 167]);
        $this->expectStreamWrite(13);
        $this->expectStreamRead(2, [129, 147]);
        $this->expectStreamRead(4, [33, 111, 149, 174]);
        $this->expectStreamRead(
            19,
            [115, 10, 246, 203, 72, 25, 252, 192, 70, 79, 244, 142, 76, 10, 230, 221, 64, 8, 240]
        );
        $message = $server->receive();

        $this->assertEquals('Receiving a message', $message);
        $this->assertEquals('text', $server->getLastOpcode());

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testRemoteClose(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerHandshake();
        $this->expectStreamWrite(null, 9);
        $server->send('Connect');

        $this->expectStreamResourceType();
        $this->expectStreamRead(2, [136, 137]);
        $this->expectStreamRead(4, [54, 79, 233, 244]);
        $this->expectStreamRead(9, [117, 35, 170, 152, 89, 60, 128, 154, 81]);
        $this->expectStreamWrite(29);
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectStreamResourceType();
        $message = $server->receive();

        $this->expectStreamResourceType();
        $this->assertFalse($server->isConnected());
        $this->assertEquals(17260, $server->getCloseStatus());
        $this->assertNull($server->getLastOpcode());

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testSetTimeout(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerHandshake();
        $this->expectStreamWrite(null, 9);
        $server->send('Connect');

        $this->expectStreamResourceType();
        $this->expectStreamTimeout(300);
        $server->setTimeout(300);

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testFailedSocketServer(): void
    {
        $this->expectStreamFactory();
        $server = new Server(['port' => 9999]);
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectFactoryCreateSockerServer(9999);
        $this->expectSockerServerConstruct(9999, new RuntimeException("Could not create socket for 'test'."));
        $this->expectFactoryCreateSockerServer(10000);
        $this->expectSockerServerConstruct(10000, new RuntimeException("Could not create socket for 'test'."));
        $this->expectException('WebSocket\ConnectionException');
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Could not open listening socket:');
        $server->accept();

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testFailedConnect(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerAccept(new RuntimeException("Could not accept on socket."));
        $this->expectException('WebSocket\ConnectionException');
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Server failed to connect');
        $server->send('Connect');

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testFailedHttp(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketServer();
        $server->accept();
        $this->expectServerAccept();
        $this->expectStreamConstruct();
        $this->expectStreamMetadata();
        $this->expectStreamRemoteName('localhost:8000');
        $this->expectStreamReadLine(1024, "FAIL /my/mock/path HTTP/1.1\r\n\r\n");
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectException('WebSocket\ConnectionException');
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('No GET in request');
        $server->send('Connect');

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testFailedWsKey(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketServer();
        $server->accept();
        $this->expectServerAccept();
        $this->expectStreamConstruct();
        $this->expectStreamMetadata();
        $this->expectStreamRemoteName('localhost:8000');
        $this->expectStreamReadLine(1024, "GET /my/mock/path HTTP/1.1\r\n\r\n");
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectException('WebSocket\ConnectionException');
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Client had no Key in upgrade request');
        $server->send('Connect');

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testSendBadOpcode(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerHandshake();
        $this->expectStreamWrite(null, 9);
        $server->send('Connect');

        $this->expectStreamResourceType();
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectException('WebSocket\BadOpcodeException');
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Bad opcode \'bad\'.  Try \'text\' or \'binary\'.');
        $server->send('Bad Opcode', 'bad');

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testRecieveBadOpcode(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerHandshake();
        $this->expectStreamWrite(null, 9);
        $server->send('Connect');

        $this->expectStreamResourceType();
        $this->expectStreamRead(2, [140, 115]);
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectException('WebSocket\ConnectionException');
        $this->expectExceptionCode(1026);
        $this->expectExceptionMessage('Bad opcode in websocket frame: 12');
        $message = $server->receive();

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testBrokenWrite(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerHandshake();
        $this->expectStreamWrite(null, 14);
        $this->expectStreamResourceType();
        $this->expectStreamMetadata(['eof' => true, 'mode' => 'rw', 'seekable' => false]);
        $this->expectStreamClose();
        $this->expectStreamMetadata(['eof' => true, 'mode' => 'rw', 'seekable' => false]);
        $this->expectStreamClose();
        $this->expectStreamMetadata(['eof' => true, 'mode' => 'rw', 'seekable' => false]);
        $this->expectException('WebSocket\ConnectionException');
        $this->expectExceptionCode(1025);
        $this->expectExceptionMessage('Could only write 14 out of 18 bytes.');
        $server->send('Failing to write');

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testFailedWrite(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerHandshake();
        $this->expectStreamWrite(null, new RuntimeException('Stream is not writable.'));
        $this->expectStreamMetadata(['timed_out' => true, 'mode' => 'rw', 'seekable' => false]);
        $this->expectStreamClose();
        $this->expectStreamMetadata(['timed_out' => true, 'mode' => 'rw', 'seekable' => false]);
        $this->expectException('WebSocket\TimeoutException');
        $this->expectExceptionCode(1024);
        $this->expectExceptionMessage('Client write timeout');
        $server->send('Failing to write');

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testBrokenRead(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerHandshake();
        $this->expectStreamRead(null, new RuntimeException('Stream is not readable.'));
        $this->expectStreamMetadata(['eof' => true, 'mode' => 'rw', 'seekable' => false]);
        $this->expectStreamClose();
        $this->expectStreamMetadata(['eof' => true, 'mode' => 'rw', 'seekable' => false]);
        $this->expectException('WebSocket\ConnectionException');
        $this->expectExceptionCode(1025);
        $this->expectExceptionMessage('Broken frame, read 0 of stated 2 bytes.');
        $server->receive();

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testEmptyRead(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerHandshake();
        $this->expectStreamRead(null, '');
        $this->expectStreamResourceType();
        $this->expectStreamMetadata(['timed_out' => true, 'mode' => 'rw', 'seekable' => false]);
        $this->expectStreamClose();
        $this->expectStreamMetadata(['timed_out' => true, 'mode' => 'rw', 'seekable' => false]);
        $this->expectStreamClose();
        $this->expectStreamMetadata(['timed_out' => true, 'mode' => 'rw', 'seekable' => false]);
        $this->expectException('WebSocket\TimeoutException');
        $this->expectExceptionCode(1024);
        $this->expectExceptionMessage('Empty read; connection dead?');
        $server->receive();

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testFrameFragmentation(): void
    {
        $this->expectStreamFactory();
        $server = new Server(['filter' => ['text', 'binary', 'pong', 'close']]);
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerHandshake();

        $this->expectStreamRead(2, [1, 136]);
        $this->expectStreamRead(4, [105, 29, 187, 18]);
        $this->expectStreamRead(8, [36, 104, 215, 102, 0, 61, 221, 96]);
        $this->expectStreamRead(2, [138, 139]);
        $this->expectStreamRead(4, [1, 1, 1, 1]);
        $this->expectStreamRead(11, [82, 100, 115, 119, 100, 115, 33, 113, 104, 111, 102]);

        $message = $server->receive();
        $this->assertEquals('Server ping', $message);
        $this->assertEquals('pong', $server->getLastOpcode());

        $this->expectStreamResourceType();
        $this->expectStreamRead(2, [0, 136]);
        $this->expectStreamRead(4, [221, 240, 46, 69]);
        $this->expectStreamRead(8, [188, 151, 67, 32, 179, 132, 14, 49]);
        $this->expectStreamRead(2, [128, 131]);
        $this->expectStreamRead(4, [9, 60, 117, 193]);
        $this->expectStreamRead(3, [108, 79, 1]);

        $message = $server->receive();
        $this->assertEquals('Multi fragment test', $message);
        $this->assertEquals('text', $server->getLastOpcode());

        $this->expectStreamResourceType();
        $this->expectStreamRead(2, [136, 137]);
        $this->expectStreamRead(4, [54, 79, 233, 244]);
        $this->expectStreamRead(9, [117, 35, 170, 152, 89, 60, 128, 154, 81]);
        $this->expectStreamWrite(23);
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectStreamResourceType();
        $this->expectStreamResourceType();

        $message = $server->receive();
        $this->assertEquals('Closing', $message);
        $this->assertFalse($server->isConnected());
        $this->assertEquals(17260, $server->getCloseStatus());
        $this->assertEquals('close', $server->getLastOpcode());

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testMessageFragmentation(): void
    {
        $this->expectStreamFactory();
        $server = new Server(['filter' => ['text', 'binary', 'pong', 'close'], 'return_obj' => true]);
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerHandshake();

        $this->expectStreamRead(2, [1, 136]);
        $this->expectStreamRead(4, [105, 29, 187, 18]);
        $this->expectStreamRead(8, [36, 104, 215, 102, 0, 61, 221, 96]);
        $this->expectStreamRead(2, [138, 139]);
        $this->expectStreamRead(4, [1, 1, 1, 1]);
        $this->expectStreamRead(11, [82, 100, 115, 119, 100, 115, 33, 113, 104, 111, 102]);

        $message = $server->receive();
        $this->assertInstanceOf('WebSocket\Message\Message', $message);
        $this->assertInstanceOf('WebSocket\Message\Pong', $message);
        $this->assertEquals('Server ping', $message->getContent());
        $this->assertEquals('pong', $message->getOpcode());

        $this->expectStreamResourceType();
        $this->expectStreamRead(2, [0, 136]);
        $this->expectStreamRead(4, [221, 240, 46, 69]);
        $this->expectStreamRead(8, [188, 151, 67, 32, 179, 132, 14, 49]);
        $this->expectStreamRead(2, [128, 131]);
        $this->expectStreamRead(4, [9, 60, 117, 193]);
        $this->expectStreamRead(3, [108, 79, 1]);

        $message = $server->receive();
        $this->assertInstanceOf('WebSocket\Message\Message', $message);
        $this->assertInstanceOf('WebSocket\Message\Text', $message);
        $this->assertEquals('Multi fragment test', $message->getContent());
        $this->assertEquals('text', $message->getOpcode());

        $this->expectStreamResourceType();
        $this->expectStreamRead(2, [136, 137]);
        $this->expectStreamRead(4, [54, 79, 233, 244]);
        $this->expectStreamRead(9, [117, 35, 170, 152, 89, 60, 128, 154, 81]);
        $this->expectStreamWrite(23);
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectStreamResourceType();

        $message = $server->receive();
        $this->assertInstanceOf('WebSocket\Message\Message', $message);
        $this->assertInstanceOf('WebSocket\Message\Close', $message);
        $this->assertEquals('Closing', $message->getContent());
        $this->assertEquals('close', $message->getOpcode());

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testConvenicanceMethods(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->assertNull($server->getName());
        $this->assertNull($server->getRemoteName());
        $this->assertEquals('WebSocket\Server(closed)', "{$server}");

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerHandshake();
        $this->expectStreamWrite([129, 7, null, null, null, null, null, null, null]);
        $server->text('Connect');

        $this->expectStreamResourceType();
        $this->expectStreamWrite([
            130, 20, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null,
            null, null, null, null, null
        ]);
        $server->binary(base64_encode('Binary content'));

        $this->expectStreamResourceType();
        $this->expectStreamWrite([137, 0]);
        $server->ping();

        $this->expectStreamResourceType();
        $this->expectStreamWrite([138, 0]);
        $server->pong();

        $this->expectStreamResourceType();
        $this->expectStreamLocalName('127.0.0.1:12345');
        $this->assertEquals('127.0.0.1:12345', $server->getName());
        $this->expectStreamResourceType();
        $this->expectStreamRemoteName('127.0.0.1:8000');
        $this->expectStreamResourceType();
        $this->expectStreamLocalName('127.0.0.1:12345');
        $this->assertEquals('127.0.0.1:8000', $server->getRemoteName());
        $this->assertEquals('WebSocket\Server(127.0.0.1:12345)', "{$server}");

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testUnconnectedServer(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->assertFalse($server->isConnected());
        $server->setTimeout(30);
        $server->close();
        $this->assertFalse($server->isConnected());
        $this->assertNull($server->getName());
        $this->assertNull($server->getRemoteName());
        $this->assertNull($server->getCloseStatus());
    }

    public function testFailedHandshake(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerAccept();
        $this->expectStreamConstruct();
        $this->expectStreamMetadata();
        $this->expectStreamRemoteName('localhost:8000');
        $this->expectStreamReadLine(1024, new RuntimeException('Stream is not readable.'));
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectException('WebSocket\ConnectionException');
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Client handshake error');
        $server->send('Connect');
        $this->assertFalse($server->isConnected());

        $this->expectStreamDestruct();
        unset($server);
    }

    public function testServerDisconnect(): void
    {
        $this->expectStreamFactory();
        $server = new Server();
        $server->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketServer();
        $server->accept();

        $this->expectServerHandshake();
        $this->expectStreamWrite();
        $server->send('Connect');

        $this->expectStreamResourceType();
        $this->assertTrue($server->isConnected());

        $this->expectStreamResourceType();
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $server->disconnect();
        $this->assertFalse($server->isConnected());

        unset($server);
    }

    public function testDeprecated(): void
    {
        $server = new Server();
        (new ErrorHandler())->withAll(function () use ($server) {
            $this->assertNull($server->getPier());
        }, function ($exceptions, $result) {
            $this->assertEquals(
                'getPier() is deprecated and will be removed in future version. Use getRemoteName() instead.',
                $exceptions[0]->getMessage()
            );
        }, E_USER_DEPRECATED);
    }
}
