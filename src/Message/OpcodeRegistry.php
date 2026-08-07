<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Message;

use DomainException;
use InvalidArgumentException;
use RangeException;
use WebSocket\Exception\BadOpcodeException;

/**
 * WebSocket\Message\OpcodeRegistry class.
 * Mapping opcode <-> Message implementation class
 */
class OpcodeRegistry
{
    /** @var array<int<1, 15>, class-string> $map */
    protected array $map = [
        1 => Text::class,
        2 => Binary::class,
        8 => Close::class,
        9 => Ping::class,
        10 => Pong::class,
    ];

    /**
     * @param class-string $classname
     * @return int<1, 15>
     * @throws BadOpcodeException
     */
    public function getOpcode(string $classname): int
    {
        $opcode = array_search($classname, $this->map);
        if (!is_int($opcode) || $opcode < 1 || $opcode > 15) {
            throw new BadOpcodeException(sprintf(
                'Opcode must be integer in range 1-15, %s provided',
                json_encode($opcode)
            ));
        }
        return $opcode;
    }

    /**
     * @param int<1, 15> $opcode
     * @return Message
     * @throws BadOpcodeException
     */
    public function createMessage(int $opcode): Message
    {
        if ($opcode < 1 || $opcode > 15) {
            throw new BadOpcodeException(sprintf(
                'Opcode must be integer in range 1-15, %s provided',
                json_encode($opcode)
            ));
        }
        $classname = $this->map[$opcode] ?? null;
        if (!is_string($classname) || !class_exists($classname)) {
            throw new BadOpcodeException(sprintf(
                'Implementation class %s for opcode %s not found',
                json_encode($classname),
                $opcode
            ));
        }
        $class = new $classname();
        if (!$class instanceof Message) {
            throw new BadOpcodeException(sprintf(
                'Implementation class %s must extend %s',
                $class::class,
                Message::class,
            ));
        }
        return $class;
    }

    /**
     * @param int<1, 15> $opcode
     * @param class-string $classname
     * @throws BadOpcodeException
     */
    public function register(int $opcode, string $classname): void
    {
        if ($opcode < 1 || $opcode > 15) {
            throw new RangeException(sprintf(
                'Opcode must be integer in range 1-15, %s provided',
                json_encode($opcode)
            ));
        }
        if (empty($classname) || !class_exists($classname)) {
            throw new DomainException(sprintf(
                'Implementation class %s not found',
                json_encode($classname)
            ));
        }
        $class = new $classname();
        if (!$class instanceof Message) {
            throw new DomainException(sprintf(
                'Implementation class %s must extend %s',
                $class::class,
                Message::class,
            ));
        }
        $this->map[$opcode] = $classname;
    }
}
