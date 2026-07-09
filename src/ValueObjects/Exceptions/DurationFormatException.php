<?php

namespace Davos\Graphy\ValueObjects\Exceptions;

use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;

class DurationFormatException extends CommandDefinitionException
{
    public static function invalidDuration(string $rrdDuration): self
    {
        return new self(sprintf(
            'Invalid duration "%s". Expected number optionally followed by one of: s, m, h, d, w, M, y',
            $rrdDuration,
        ));
    }
}
