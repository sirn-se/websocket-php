[Documentation](../Index.md) > Classes > Index

# Websocket: Classes index

## Root

* Class [WebSocket\Client](Client.md) - The WebSocket client
* Class [WebSocket\Connection](Connection.md) - WebSocket connection
* Class [WebSocket\Server](Server.md) - The WebSocket server

## Exceptions

* Class [WebSocket\Exception\BadOpcodeException](Exception.BadOpcodeException.md)
* Class [WebSocket\Exception\BadUriException](Exception.BadUriException.md)
* Class [WebSocket\Exception\ClientException](Exception.ClientException.md)
* Class [WebSocket\Exception\CloseException](Exception.CloseException.md)
* Class [WebSocket\Exception\ConnectionClosedException](Exception.ConnectionClosedException.md)
* Class [WebSocket\Exception\ConnectionFailureException](Exception.ConnectionFailureException.md)
* Interface [WebSocket\Exception\ConnectionLevelInterface](Exception.ConnectionLevelInterface.md)
* Class [WebSocket\Exception\ConnectionTimeoutException](Exception.ConnectionTimeoutException.md)
* Interface [WebSocket\Exception\MessageLevelInterface](Exception.MessageLevelInterface.md)
* Class [WebSocket\Exception\HandshakeException](Exception.HandshakeException.md)
* Abstract class [WebSocket\Exception\Exception](Exception.Exception.md)

## Frame handling

* Class [WebSocket\Frame\FrameHandler](Frame.FrameHandler.md)
* Class [WebSocket\Frame\Frame](Frame.Frame.md)

## HTTP handling

* Class [WebSocket\Http\HttpHandler](Http.HttpHandler.md)
* Abstract class [WebSocket\Http\Message](Http.Message.md)
* Class [WebSocket\Http\Request](Http.Request.md)
* Class [WebSocket\Http\Response](Http.Response.md)
* Class [WebSocket\Http\ServerRequest](Http.ServerRequest.md)

## Message handling

* Class [WebSocket\Message\Binary](Message.Binary.md)
* Class [WebSocket\Message\Close](Message.Close.md)
* Abstract class [WebSocket\Message\Message](Message.Message.md)
* Class [WebSocket\Message\MessageHandler](Message.MessageHandler.md)
* Class [WebSocket\Message\Ping](Message.Ping.md)
* Class [WebSocket\Message\Pong](Message.Pong.md)
* Class [WebSocket\Message\Text](Message.Text.md)

## Middleware handling

* Class [WebSocket\Middleware\Callback](Middleware.Callback.md)
* Class [WebSocket\Middleware\CloseHandler](Middleware.CloseHandler.md)
* Class [WebSocket\Middleware\MiddlewareHandler](Middleware.MiddlewareHandler.md)
* Interface [WebSocket\Middleware\MiddlewareInterface](Middleware.MiddlewareInterface.md)
* Class [WebSocket\Middleware\PingResponder](Middleware.PingResponder.md)
* Interface [WebSocket\Middleware\ProcessIncomingInterface](Middleware.ProcessIncomingInterface.md)
* Interface [WebSocket\Middleware\ProcessOutgoingInterface](Middleware.ProcessOutgoingInterface.md)
* Class [WebSocket\Middleware\ProcessStack](Middleware.ProcessStack.md)

## Traits

* Trait [WebSocket\Trait\ListenerTrait](Trait.ListenerTrait.md)
* Trait [WebSocket\Trait\OpcodeTrait](Trait.OpcodeTrait.md)
* Trait [WebSocket\Trait\SendMethodsTrait](Trait.SendMethodsTrait.md)
