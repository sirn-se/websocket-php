<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Runtime;

use Closure;
use Countable;
use Generator;
use IteratorAggregate;
use Phrity\Http\HttpFactory;
use Phrity\Net\SocketStream;
use WebSocket\{
    Configuration,
    Connection,
};
use WebSocket\Exception\RunnerException;

/**
 * Class wrapping connections.
 *
 * @implements IteratorAggregate<non-empty-string, Connection>
 */
class Connections implements Countable, IteratorAggregate
{
    /** @var array<non-empty-string, Connection> $connections */
    private array $connections = [];

    private bool $pushMasked;
    private bool $pullMaskedRequired;
    private HttpFactory $httpFactory;
    private Configuration $configuration;

    public function __construct(
        bool $pushMasked,
        bool $pullMaskedRequired,
        HttpFactory $httpFactory,
        Configuration $configuration,
    ) {
        $this->pushMasked = $pushMasked;
        $this->pullMaskedRequired = $pullMaskedRequired;
        $this->httpFactory = $httpFactory;
        $this->configuration = $configuration;
    }

    public function create(SocketStream $stream, bool $ssl): Connection
    {
        return new Connection(
            $stream,
            $this->pushMasked,
            $this->pullMaskedRequired,
            $ssl,
            $this->httpFactory,
            $this->configuration,
        );
    }


    /* ---------- Accessors ---------------------------------------------------------------------------------------- */

    /**
     * @param non-empty-string $identity
     */
    public function has(string $identity): bool
    {
        return array_key_exists($identity, $this->connections);
    }

    /**
     * @param non-empty-string $identity
     */
    public function get(string $identity): Connection|null
    {
        return $this->connections[$identity] ?? null;
    }

    public function first(): Connection|null
    {
        $identity = array_key_first($this->connections);
        return $identity === null ? null : $this->get($identity);
    }

    public function getIterator(): Generator
    {
        return (function () {
            foreach ($this->connections as $identity => $connection) {
                yield $identity => $connection;
            }
        })();
    }

    /**
     * @return array<non-empty-string, Connection>
     */
    public function toArray(): array
    {
        return $this->connections;
    }


    /* ---------- State methods ------------------------------------------------------------------------------------ */

    /**
     * @return int<0, max>
     */
    public function count(): int
    {
        return count($this->connections);
    }

    public function isEmpty(): bool
    {
        return count($this->connections) === 0;
    }


    /* ---------- Operators ---------------------------------------------------------------------------------------- */

    public function reset(): void
    {
        $this->connections = [];
    }

    /**
     * @param non-empty-string $identity
     */
    public function detach(string $identity): void
    {
        if ($this->has($identity)) {
            unset($this->connections[$identity]);
        }
    }

    public function attach(Connection $connection): string
    {
        $identity = $connection->getIdentity();
        if ($this->has($identity)) {
            throw new RunnerException("Connection with identity {$identity} already attached");
        }
        $this->connections[$identity] = $connection;
        return $identity;
    }

    /* ---------- Collection methods ------------------------------------------------------------------------------- */

    /**
     * @param Closure(Connection): void $callback
     */
    public function walk(Closure $callback): void
    {
        foreach ($this->connections as $connection) {
            $callback($connection);
        }
    }

    /**
     * @param Closure(Connection): bool $callback
     */
    public function filter(Closure $callback): self
    {
        $new = clone $this;
        $new->connections = array_filter($this->connections, $callback);
        return $new;
    }
}
