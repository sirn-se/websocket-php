<?php

namespace WebSocket;

abstract class Exception extends \Exception
{
    // General error codes
    public const TIMED_OUT = 1024;
    public const EOF = 1025;
    public const BAD_OPCODE = 1026;
    public const BAD_URI = 1027;

    // Client error codes
    public const CLIENT_CONNECT_ERR = 1100;
    public const CLIENT_HANDSHAKE_ERR = 1101;

    // Server error codes
    public const SERVER_SOCKET_ERR = 1200;
    public const SERVER_ACCEPT_ERR = 1201;
    public const SERVER_HANDSHAKE_ERR = 1202;
}
