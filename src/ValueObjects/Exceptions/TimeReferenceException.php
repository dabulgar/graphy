<?php

namespace Davos\Graphy\ValueObjects\Exceptions;

use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;

class TimeReferenceException extends CommandDefinitionException
{
    public static function timeTooShort(string $time): self
    {
        return new self(
            sprintf(
                "Invalid time reference: '%s'. Expected timestamp, anchor (now/end/start), or relative expression like 'now-1h'.",
                $time
            )
        );
    }

    public static function invalidTimeReference(string $time, int $code = 0, ?\Throwable $throwable = null): self
    {
        return new self(
            sprintf(
                "Invalid time reference: '%s'. Unable to parse value.",
                $time
            ),
            code: $code,
            previous: $throwable
        );
    }

    public static function missingAnchorInResolvedArray(string $anchor): self
    {
        return new self(
            sprintf(
                "Cannot resolve '%s' anchor. It is not provided in resolved values.",
                $anchor
            )
        );
    }
}