<?php

namespace Davos\Graphy\ValueObjects\Exceptions;

use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;

class RoundRobinArchiveDefinitionException extends CommandDefinitionException
{
    public static function invalidCf(string $definition, array $allowedTypes): self
    {
        return new self(sprintf(
            'Invalid CF of RRA "%s". Expected to be in: %s',
            $definition,
            implode(', ', $allowedTypes)
        ));
    }

    public static function invalidXff(string $definition): self
    {
        return new self(sprintf(
            'Invalid XFF of RRA "%s". Allowed values between 0 and 1',
            $definition,
        ));
    }
}
