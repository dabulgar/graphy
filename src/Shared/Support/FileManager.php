<?php

namespace Davos\Graphy\Shared\Support;

use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;

class FileManager
{
    public static function ensureRrdExtension(string $fileName): string
    {
        if (!str_ends_with($fileName, ".rrd")) {
            return $fileName . '.rrd';
        }

        return $fileName;
    }

    public static function ensureFileExists(string $file): true
    {
        if (!is_file($file)) {
            throw CommandDefinitionException::fromMessage(sprintf('File "%s" not found.', $file));
        }

        return true;
    }
}