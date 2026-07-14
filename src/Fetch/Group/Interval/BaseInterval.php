<?php

namespace Davos\Graphy\Fetch\Group\Interval;

use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;

abstract class BaseInterval
{
    final public function __construct(
        protected readonly int $intervals = 1
    ) {
        if ($this->intervals <= 0) {
            throw CommandDefinitionException::fromMessage('Interval must be greater than zero.');
        }
    }

    abstract public function getInterval(int $timestamp, string $timezone = 'UTC'): array;

    public static function for(int $intervals): self
    {
        return new static($intervals);
    }
}