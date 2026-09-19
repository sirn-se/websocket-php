<?php

/**
 * Copyright (C) 2014-2026 Textalk and contributors.
 * This file is part of Websocket PHP and is free software under the ISC License.
 */

namespace WebSocket\Message;

use DomainException;
use InvalidArgumentException;
use RangeException;
use ReflectionClass;
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
        if ($opcode === false) {
            throw new BadOpcodeException(
                'Implementation class "{class}" not found',
                class: $classname,
            );
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
            throw new BadOpcodeException(
                'Opcode must be integer in range 1-15, {opcode} provided',
                opcode: $opcode,
            );
        }
        $classname = $this->map[$opcode] ?? null;
        if (!is_string($classname)) {
            throw new BadOpcodeException(
                'Implementation class for opcode {opcode} not registered',
                opcode: $opcode,
            );
        }
        if (!class_exists($classname)) {
            throw new BadOpcodeException(
                'Implementation class "{class}" for opcode {opcode} not found',
                class: $classname,
                opcode: $opcode,
            );
        }
        $reflector = new ReflectionClass($classname);
        if (!$reflector->isSubclassOf(Message::class)) {
            throw new BadOpcodeException(
                'Implementation class "{class}" must extend "{parent}"',
                class: $classname,
                parent: Message::class,
            );
        }
        return $reflector->newInstanceWithoutConstructor();
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
                $opcode
            ));
        }
        if (empty($classname) || !class_exists($classname)) {
            throw new DomainException(sprintf(
                'Implementation class %s not found',
                json_encode($classname)
            ));
        }
        $reflector = new ReflectionClass($classname);
        if (!$reflector->isSubclassOf(Message::class)) {
            throw new DomainException(sprintf(
                'Implementation class "%s" must extend "%s"',
                $classname,
                Message::class,
            ));
        }
        $this->map[$opcode] = $classname;
    }
}
