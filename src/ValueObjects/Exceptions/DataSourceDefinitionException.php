<?php

namespace Davos\Graphy\ValueObjects\Exceptions;

use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;

class DataSourceDefinitionException extends CommandDefinitionException
{
    public static function invalidName(string $definition): self
    {
        return new self(sprintf(
            'Invalid data source name in "%s". Expected pattern: [%s]',
            $definition,
            'a-zA-Z0-9_'
        ));
    }

    public static function invalidType(string $definition, array $allowedTypes): self
    {
        return new self(sprintf(
            'Invalid data source type in "%s". Allowed types: %s',
            $definition,
            implode(', ', $allowedTypes)
        ));
    }

    public static function invalidMinValue(string $definition): self
    {
        return new self(sprintf(
            'Invalid minimum value in "%s". Expected numeric or "U"',
            $definition,
        ));
    }

    public static function invalidMaxValue(string $definition): self
    {
        return new self(sprintf(
            'Invalid maximum value in "%s". Expected numeric or "U"',
            $definition,
        ));
    }

    public static function invalidExpression(string $definition): self
    {
        return new self(sprintf(
            'Invalid expression in "%s". Expression must not be empty',
            $definition
        ));
    }
}
