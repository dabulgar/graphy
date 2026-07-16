<?php

namespace Davos\Graphy\Shared\Exceptions;

class CommandDefinitionException extends GraphyException
{
    public static function fromMessage(string $message): self
    {
        return new self($message);
    }

    public static function invalidFlagInstance(): self
    {
        return new self("All flags must be instances of Flag.");
    }

    public static function missingRequiredFlag(string $action, string $flag): self
    {
        return new self(
            sprintf("Invalid %s options: required flag %s is missing.", $action, $flag)
        );
    }

    public static function flagNotAllowed(string $action, string $flag): self
    {
        return new self(
            sprintf("Invalid %s options: flag '%s' is not allowed.", $action, $flag),
        );
    }

    public static function dataSourceNotFoundInModel(string $dataSource): self
    {
        return new self(
            sprintf("Invalid data source '%s'.", $dataSource)
        );
    }

    public static function noDataProvidedForUpdate(): self
    {
        return new self("Update data cannot be empty.");
    }

    public static function invalidConsolidationFunction(string $action, string $cf, array $allowed): self
    {
        return new self(sprintf(
            'Invalid consolidation function "%s" for %s. Allowed values are: %s.',
            $cf,
            $action,
            implode(', ', $allowed)
        ));
    }
}
