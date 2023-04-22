<?php

/**
 * Test case for Client.
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

class ClientTest extends TestCase
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

    public function testClientMasked(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->assertFalse($client->isConnected());
        $this->assertEquals(null, $client->getLastOpcode());
        $this->assertEquals(4096, $client->getFragmentSize());

        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamWrite();
        $client->send('Connect');

        $this->expectStreamResourceType();
        $this->expectStreamWrite(23);
        $client->send('Sending a message');

        $this->expectStreamResourceType();
        $this->expectStreamRead(2, [129, 147]);
        $this->expectStreamRead(4, [33, 111, 149, 174]);
        $this->expectStreamRead(19, [
            115, 10, 246, 203, 72, 25, 252, 192, 70, 79, 244, 142, 76, 10, 230, 221, 64, 8, 240
        ]);
        $message = $client->receive();

        $this->assertEquals('text', $client->getLastOpcode());
        $this->expectStreamResourceType();
        $this->assertTrue($client->isConnected());
        $this->expectStreamResourceType();
        $this->assertNull($client->getCloseStatus());

        $this->expectStreamResourceType();
        $this->expectStreamWrite(12);
        $this->expectStreamRead(2, [136, 154]);
        $this->expectStreamRead(4, [98, 250, 210, 113]);
        $this->expectStreamRead(26, [
            97, 18, 145, 29, 13, 137, 183, 81, 3, 153, 185, 31, 13, 141, 190,
            20, 6, 157, 183, 21, 88, 218, 227, 65, 82, 202
        ]);
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectStreamResourceType();
        $client->close();

        $this->expectStreamResourceType();
        $this->assertFalse($client->isConnected());
        $this->assertEquals(1000, $client->getCloseStatus());

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testClientExtendedUrl(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path?my_query=yes#my_fragment');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientHandshake(path: '/my/mock/path?my_query=yes');
        $this->expectStreamWrite();
        $client->send('Connect');

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testClientNoPath(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientHandshake(path: '/');
        $this->expectStreamWrite();
        $client->send('Connect');

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testClientRelativePath(): void
    {
        $uri = new Uri('ws://localhost:8000');
        $uri = $uri->withPath('my/mock/path');

        $this->expectStreamFactory();
        $client = new Client($uri);
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamWrite();
        $client->send('Connect');

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testClientWsDefaultPort(): void
    {
        $uri = new Uri('ws://localhost');
        $uri = $uri->withPath('my/mock/path');

        $this->expectStreamFactory();
        $client = new Client($uri);
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient(host: 'localhost:80');
        $this->expectClientHandshake(host: 'localhost:80');
        $this->expectStreamWrite();
        $client->send('Connect');

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testClientWssDefaultPort(): void
    {
        $uri = new Uri('wss://localhost');
        $uri = $uri->withPath('my/mock/path');

        $this->expectStreamFactory();
        $client = new Client($uri);
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient(scheme: 'ssl', host: 'localhost:443');
        $this->expectClientHandshake(host: 'localhost:443');
        $this->expectStreamWrite();
        $client->send('Connect');

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testClientWithTimeout(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path', ['timeout' => 300]);
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient(timeout: 300);
        $this->expectClientHandshake(timeout: 300);
        $this->expectStreamWrite();
        $client->send('Connect');

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testClientWithContext(): void
    {
        $context = stream_context_create(['ssl' => ['verify_peer' => false]]);

        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path', ['context' => $context]);
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamWrite();
        $client->send('Connect');

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testClientAuthed(): void
    {
        $this->expectStreamFactory();
        $client = new Client('wss://usename:password@localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient(scheme: 'ssl');
        $this->expectClientHandshake(headers: "authorization: Basic dXNlbmFtZTpwYXNzd29yZA==\r\n");
        $this->expectStreamWrite();
        $client->send('Connect');

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testWithHeaders(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path', [
            'origin' => 'Origin header',
            'headers' => ['Generic-header' => 'Generic content'],
        ]);
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientHandshake(headers: "origin: Origin header\r\nGeneric-header: Generic content\r\n");
        $this->expectStreamWrite();
        $client->send('Connect');

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testPayload128(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());
        $client->setFragmentSize(65540);

        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamWrite();
        $client->send('Connect');

        $payload = file_get_contents(__DIR__ . '/mock/payload.128.txt');

        $this->expectStreamResourceType();
        $this->expectStreamWrite(136);
        $client->send($payload, 'text');

        $this->expectStreamResourceType();
        $this->expectStreamRead(2, [129, 126]);
        $this->expectStreamRead(2, [0, 128]);
        $this->expectStreamRead(128, substr($payload, 0, 132));

        $message = $client->receive();
        $this->assertEquals($payload, $message);

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testPayload65536(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());
        $client->setFragmentSize(65540);

        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamWrite();
        $client->send('Connect');

        $payload = file_get_contents(__DIR__ . '/mock/payload.65536.txt');

        $this->expectStreamResourceType();
        $this->expectStreamWrite(65550);
        $client->send($payload, 'text');

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
        $message = $client->receive();

        $this->assertEquals($payload, $message);
        $this->assertEquals(65540, $client->getFragmentSize());

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testMultiFragment(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamWrite();
        $client->send('Connect');

        $this->expectStreamResourceType();
        $client->setFragmentSize(8);

        $this->expectStreamResourceType();
        $this->expectStreamWrite([1, 136, null, null, null, null, null, null, null, null, null, null, null, null]);
        $this->expectStreamWrite([0, 136, null, null, null, null, null, null, null, null, null, null, null, null]);
        $this->expectStreamWrite([128, 131, null, null, null, null, null, null, null]);
        $client->send('Multi fragment test');

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
        $message = $client->receive();

        $this->assertEquals('Multi fragment test', $message);
        $this->assertEquals(8, $client->getFragmentSize());

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testPingPong(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamWrite();
        $client->send('Connect');

        $this->expectStreamResourceType();
        $this->expectStreamWrite();
        $client->send('Server ping', 'ping');

        $this->expectStreamResourceType();
        $this->expectStreamWrite();
        $client->send('', 'ping');

        $this->expectStreamResourceType();
        $this->expectStreamRead(2, [138, 139]);
        $this->expectStreamRead(4, [1, 1, 1, 1]);
        $this->expectStreamRead(11, [82, 100, 115, 119, 100, 115, 33, 113, 104, 111, 102]);
        $this->expectStreamRead(2, [138, 128]);
        $this->expectStreamRead(4, [1, 1, 1, 1]);
        $this->expectStreamRead(2, [137, 139]);
        $this->expectStreamRead(4, [180, 77, 192, 201]);
        $this->expectStreamRead(11, [247, 33, 169, 172, 218, 57, 224, 185, 221, 35, 167]);
        $this->expectStreamWrite();
        $this->expectStreamRead(2, [129, 147]);
        $this->expectStreamRead(4, [33, 111, 149, 174]);
        $this->expectStreamRead(19, [
            115, 10, 246, 203, 72, 25, 252, 192, 70, 79, 244, 142, 76, 10, 230, 221, 64, 8, 240
        ]);

        $message = $client->receive();

        $this->assertEquals('Receiving a message', $message);
        $this->assertEquals('text', $client->getLastOpcode());

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testRemoteClose(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamWrite();
        $client->send('Connect');

        $this->expectStreamResourceType();
        $this->expectStreamRead(2, [136, 137]);
        $this->expectStreamRead(4, [54, 79, 233, 244]);
        $this->expectStreamRead(9, [117, 35, 170, 152, 89, 60, 128, 154, 81]);
        $this->expectStreamWrite();
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectStreamResourceType();

        $message = $client->receive();
        $this->assertNull($message);

        $this->expectStreamResourceType();
        $this->assertFalse($client->isConnected());
        $this->assertEquals(17260, $client->getCloseStatus());
        $this->assertNull($client->getLastOpcode());

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testSetTimeout(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamWrite();
        $client->send('Connect');

        $this->expectStreamResourceType();
        $this->expectStreamTimeout(300);
        $client->setTimeout(300);

        $this->expectStreamResourceType();
        $this->assertTrue($client->isConnected());

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testReconnect(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamWrite();
        $client->send('Connect');

        $this->expectStreamResourceType();
        $this->assertTrue($client->isConnected());
        $this->expectStreamResourceType();
        $this->assertNull($client->getCloseStatus());

        $this->expectStreamResourceType();
        $this->expectStreamWrite([136, 134, null, null, null, null, null, null, null, null, null, null]);
        $this->expectStreamRead(2, [136, 154]);
        $this->expectStreamRead(4, [98, 250, 210, 113]);
        $this->expectStreamRead(26, [
            97, 18, 145, 29, 13, 137, 183, 81, 3, 153, 185, 31, 13, 141, 190,
            20, 6, 157, 183, 21, 88, 218, 227, 65, 82, 202
        ]);
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectStreamResourceType();
        $client->close();

        $this->expectStreamResourceType();
        $this->assertFalse($client->isConnected());
        $this->assertEquals(1000, $client->getCloseStatus());
        $this->assertNull($client->getLastOpcode());

        $this->expectStreamResourceType();
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamRead(2, [129, 147]);
        $this->expectStreamRead(4, [33, 111, 149, 174]);
        $this->expectStreamRead(19, [
            115, 10, 246, 203, 72, 25, 252, 192, 70, 79, 244, 142, 76, 10, 230, 221, 64, 8, 240
        ]);
        $message = $client->receive();
        $this->expectStreamResourceType();
        $this->assertTrue($client->isConnected());

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testPersistentConnection(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path', ['persistent' => true]);
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient(persistent: true);
        $this->expectClientHandshake(tell: 0);
        $this->expectStreamWrite();
        $client->send('Connect');

        $this->expectStreamResourceType();
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectStreamResourceType();
        $client->disconnect();
        $this->assertFalse($client->isConnected());

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testBadScheme(): void
    {
        $client = new Client('ws://localhost:8000/my/mock/path', ['persistent' => true]);
        $this->expectException('WebSocket\BadUriException');
        $this->expectExceptionMessage("Invalid URI scheme, must be 'ws' or 'wss'.");
        $client = new Client('bad://localhost:8000/my/mock/path');
    }

    public function testBadUri(): void
    {
        $this->expectException('WebSocket\BadUriException');
        $this->expectExceptionMessage("Invalid URI '--:this is not an uri:--' provided.");
        $client = new Client('--:this is not an uri:--');
    }

    public function testInvalidUriType(): void
    {
        $this->expectException('WebSocket\BadUriException');
        $this->expectExceptionMessage("Provided URI must be a UriInterface or string.");
        $client = new Client([]);
    }

    public function testUriInterface(): void
    {
        $uri = new Uri('ws://localhost:8000/my/mock/path');

        $this->expectStreamFactory();
        $client = new Client($uri);
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamWrite();
        $client->send('Connect');

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testBadStreamContext(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path', ['context' => 'BAD']);
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Stream context in $options[\'context\'] isn\'t a valid context');
        $client->send('Connect');

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testFailedConnection(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectException('WebSocket\ConnectionException');
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Could not open socket to "tcp://localhost:8000"');
        $this->expectSocketClient();
        $this->expectClientConnect(new RuntimeException('Test'));
        $client->send('Connect');

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testHandshakeFailure(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientConnect();
        $this->expectStreamConstruct();
        $this->expectStreamMetadata();
        $this->expectStreamResourceType();
        $this->expectStreamTimeout(5);
        $this->expectStreamWrite();
        $this->expectStreamReadLine(null, new RuntimeException('Test'));
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectException('WebSocket\ConnectionException');
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Client handshake error');

        $client->send('Connect');

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testInvalidUpgrade(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientConnect();
        $this->expectStreamConstruct();
        $this->expectStreamMetadata();
        $this->expectStreamResourceType();
        $this->expectStreamTimeout(5);
        $this->expectStreamWrite();
        $this->expectStreamReadLine(
            null,
            "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nInvalid upgrade\r\n\r\n"
        );
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectException('WebSocket\ConnectionException');
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Connection to \'ws://localhost:8000/my/mock/path\' failed');

        $client->send('Connect');

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testInvalidKey(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientConnect();
        $this->expectStreamConstruct();
        $this->expectStreamMetadata();
        $this->expectStreamResourceType();
        $this->expectStreamTimeout(5);
        $this->expectStreamWrite();
        $this->expectStreamReadLine(
            null,
            "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\n"
            . "Sec-WebSocket-Accept: BAD_KEY\r\n\r\n"
        );
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectException('WebSocket\ConnectionException');
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Server sent bad upgrade response');

        $client->send('Connect');

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testSendBadOpcode(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamClose();
        $this->expectStreamMetadata();

        $this->expectException('WebSocket\BadOpcodeException');
        $this->expectExceptionMessage('Bad opcode \'bad\'.  Try \'text\' or \'binary\'.');

        $client->send('Bad Opcode', 'bad');

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testRecieveBadOpcode(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamRead(2, [140, 115]);
        $this->expectStreamClose();
        $this->expectStreamMetadata();

        $this->expectException('WebSocket\ConnectionException');
        $this->expectExceptionCode(1026);
        $this->expectExceptionMessage('Bad opcode in websocket frame: 12');

        $message = $client->receive();

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testBrokenWrite(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamWrite(null, 18);
        $this->expectStreamResourceType();
        $this->expectStreamMetadata(['eof' => true, 'mode' => 'rw', 'seekable' => false]);
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectStreamClose();
        $this->expectStreamMetadata();

        $this->expectException('WebSocket\ConnectionException');
        $this->expectExceptionCode(1025);
        $this->expectExceptionMessage('Could only write 18 out of 22 bytes.');
        $client->send('Failing to write');

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testHandshakeError(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientConnect();
        $this->expectStreamConstruct();
        $this->expectStreamMetadata();
        $this->expectStreamResourceType();
        $this->expectStreamTimeout(5);
        $this->expectStreamWrite();
        $this->expectStreamReadLine(null, new RuntimeException('Test'));
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectException('WebSocket\ConnectionException');

        // @todo: Assign correct code
        //$this->expectExceptionCode(1024);
        $this->expectExceptionMessage('Client handshake error');

        $client->send('Connect');

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testReadTimeout(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamRead(2, new RuntimeException('Test'));
        $this->expectStreamMetadata(['timed_out' => true, 'mode' => 'rw', 'seekable' => false]);
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectException('WebSocket\TimeoutException');
        $this->expectExceptionCode(1024);
        $this->expectExceptionMessage('Client read timeout');

        $client->receive();

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testEmptyRead(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamRead(2, '');
        $this->expectStreamResourceType();
        $this->expectStreamMetadata(['timed_out' => true, 'mode' => 'rw', 'seekable' => false]);
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectException('WebSocket\TimeoutException');
        $this->expectExceptionCode(1024);
        $this->expectExceptionMessage('Empty read; connection dead?');

        $client->receive();

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testFrameFragmentation(): void
    {
        $this->expectStreamFactory();
        $client = new Client(
            'ws://localhost:8000/my/mock/path',
            ['filter' => ['text', 'binary', 'pong', 'close']]
        );
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamRead(2, [1, 136]);
        $this->expectStreamRead(4, [105, 29, 187, 18]);
        $this->expectStreamRead(8, [36, 104, 215, 102, 0, 61, 221, 96]);
        $this->expectStreamRead(2, [138, 139]);
        $this->expectStreamRead(4, [1, 1, 1, 1]);
        $this->expectStreamRead(11, [82, 100, 115, 119, 100, 115, 33, 113, 104, 111, 102]);

        $message = $client->receive();
        $this->assertEquals('Server ping', $message);
        $this->assertEquals('pong', $client->getLastOpcode());

        $this->expectStreamResourceType();
        $this->expectStreamRead(2, [0, 136]);
        $this->expectStreamRead(4, [221, 240, 46, 69]);
        $this->expectStreamRead(8, [188, 151, 67, 32, 179, 132, 14, 49]);
        $this->expectStreamRead(2, [128, 131]);
        $this->expectStreamRead(4, [9, 60, 117, 193]);
        $this->expectStreamRead(3, [108, 79, 1]);

        $message = $client->receive();
        $this->assertEquals('Multi fragment test', $message);
        $this->assertEquals('text', $client->getLastOpcode());

        $this->expectStreamResourceType();
        $this->expectStreamRead(2, [136, 137]);
        $this->expectStreamRead(4, [54, 79, 233, 244]);
        $this->expectStreamRead(9, [117, 35, 170, 152, 89, 60, 128, 154, 81]);
        $this->expectStreamWrite(23);
        $this->expectStreamClose();
        $this->expectStreamMetadata();
        $this->expectStreamResourceType();
        $this->expectStreamResourceType();

        $message = $client->receive();
        $this->assertEquals('Closing', $message);
        $this->assertFalse($client->isConnected());
        $this->assertEquals(17260, $client->getCloseStatus());
        $this->assertEquals('close', $client->getLastOpcode());

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testMessageFragmentation(): void
    {
        $this->expectStreamFactory();
        $client = new Client(
            'ws://localhost:8000/my/mock/path',
            ['filter' => ['text', 'binary', 'pong', 'close'], 'return_obj' => true]
        );
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamRead(2, [1, 136]);
        $this->expectStreamRead(4, [105, 29, 187, 18]);
        $this->expectStreamRead(8, [36, 104, 215, 102, 0, 61, 221, 96]);
        $this->expectStreamRead(2, [138, 139]);
        $this->expectStreamRead(4, [1, 1, 1, 1]);
        $this->expectStreamRead(11, [82, 100, 115, 119, 100, 115, 33, 113, 104, 111, 102]);

        $message = $client->receive();
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

        $message = $client->receive();
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

        $message = $client->receive();
        $this->assertInstanceOf('WebSocket\Message\Message', $message);
        $this->assertInstanceOf('WebSocket\Message\Close', $message);
        $this->assertEquals('Closing', $message->getContent());
        $this->assertEquals('close', $message->getOpcode());

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testConvenicanceMethods(): void
    {
        $this->expectStreamFactory();
        $client = new Client('ws://localhost:8000/my/mock/path');
        $client->setStreamFactory(new \Phrity\Net\Mock\StreamFactory());

        $this->assertNull($client->getName());
        $this->assertNull($client->getRemoteName());
        $this->assertEquals('WebSocket\Client(closed)', "{$client}");

        $this->expectSocketClient();
        $this->expectClientHandshake();
        $this->expectStreamWrite([129, 135, null, null, null, null, null, null, null, null, null, null, null]);
        $client->text('Connect');

        $this->expectStreamResourceType();
        $this->expectStreamWrite([
            130, 148, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null,
            null, null, null, null, null, null, null, null, null
        ]);
        $client->binary(base64_encode('Binary content'));

        $this->expectStreamResourceType();
        $this->expectStreamWrite([137, 128, null, null, null, null]);
        $client->ping();

        $this->expectStreamResourceType();
        $this->expectStreamWrite([138, 128, null, null, null, null]);
        $client->pong();

        $this->expectStreamResourceType();
        $this->expectStreamLocalName('127.0.0.1:12345');
        $this->assertEquals('127.0.0.1:12345', $client->getName());
        $this->expectStreamResourceType();
        $this->expectStreamRemoteName('127.0.0.1:8000');
        $this->expectStreamResourceType();
        $this->expectStreamLocalName('127.0.0.1:12345');
        $this->assertEquals('127.0.0.1:8000', $client->getRemoteName());
        $this->assertEquals('WebSocket\Client(127.0.0.1:12345)', "{$client}");

        $this->expectStreamDestruct();
        unset($client);
    }

    public function testUnconnectedClient(): void
    {
        $client = new Client('ws://localhost:8000/my/mock/path');
        $this->assertFalse($client->isConnected());
        $client->setTimeout(30);
        $client->close();
        $this->assertFalse($client->isConnected());
        $this->assertNull($client->getName());
        $this->assertNull($client->getRemoteName());
        $this->assertNull($client->getCloseStatus());
    }

    public function testDeprecated(): void
    {
        $client = new Client('ws://localhost:8000/my/mock/path');
        (new ErrorHandler())->withAll(function () use ($client) {
            $this->assertNull($client->getPier());
        }, function ($exceptions, $result) {
            $this->assertEquals(
                'getPier() is deprecated and will be removed in future version. Use getRemoteName() instead.',
                $exceptions[0]->getMessage()
            );
        }, E_USER_DEPRECATED);
    }
}
