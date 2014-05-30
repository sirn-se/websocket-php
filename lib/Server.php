<?php

namespace WebSocket;

/**
 * This is just a simple stub for testing the Client.  It could be made useful…
 */
class Server extends Base {
  protected $addr, $port, $listening;

  public function __construct(array $options = array()) {
    $this->port = isset($options['port']) ? $options['port'] : 8000;
    $this->options = $options;

    do {
      $this->listening = @stream_socket_server("tcp://0.0.0.0:$this->port", $errno, $errstr);
    } while ($this->listening === false && $this->port++ < 10000);

    if (!$this->listening) {
      throw new ConnectionException("Could not open listening socket.");
    }
  }

  public function getPort() { return $this->port; }

  public function accept() {
    $this->socket = stream_socket_accept($this->listening);

    if (array_key_exists('timeout', $this->options)) {
      stream_set_timeout($this->socket, $this->options['timeout']);
    }

    $this->performHandshake();

    return $this->socket;
  }

  protected function performHandshake() {
    $request = '';
    do {
      $buffer = stream_get_line($this->socket, 1024, "\r\n");
      $request .= $buffer . "\n";
      $metadata = stream_get_meta_data($this->socket);
    } while ($buffer !== '' && !feof($this->socket) && $metadata['unread_bytes'] > 0);

    if (!preg_match('#Sec-WebSocket-Key:\s(.*)$#mUi', $request, $matches)) {
      throw new ConnectionException("Client had no Key in upgrade request:\n" . $request);
    }

    $key = trim($matches[1]);

    /// @todo Validate key length and base 64...
    $response_key = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));


    $header = "HTTP/1.1 101 Switching Protocols\r\n"
      . "Upgrade: websocket\r\n"
      . "Connection: Upgrade\r\n"
      . "Sec-WebSocket-Accept: $response_key\r\n"
      . "Sec-WebSocket-Protocol: chat\r\n"
      . "\r\n";

    $this->write($header);
    $this->is_connected = true;
  }
}