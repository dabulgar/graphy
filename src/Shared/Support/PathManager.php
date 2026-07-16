<?php

namespace Davos\Graphy\Shared\Support;

use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;

class PathManager
{
    public static function ensureDirectories(string $fullPath, bool $createDirectories, int $directoryPermission): void
    {
        $dir = dirname($fullPath);

        if (in_array($dir, ['', '.', '..', DIRECTORY_SEPARATOR], true)) {
            return;
        }

        if (is_dir($dir)) {
            return;
        }

        if (!$createDirectories) {
            throw CommandDefinitionException::fromMessage(
                sprintf('Directory "%s" does not exist.', $dir)
            );
        }

        if (!mkdir($dir, $directoryPermission, true) && !is_dir($dir)) {
            throw CommandDefinitionException::fromMessage(sprintf('Directory "%s" was not created.', $dir));
        }
    }

    public static function prepareFullPath(string $configPath, bool|callable $configMapper, string $inputName): string
    {
        if (self::isAbsolutePath($inputName)) {
            return FileManager::ensureRrdExtension($inputName);
        }

        $inputName = FileManager::ensureRrdExtension($inputName);
        if (is_callable($configMapper)) {
            $inputName = $configMapper($inputName);

            if (!is_string($inputName)) {
                throw CommandDefinitionException::fromMessage(
                    'Path mapper must return a string.'
                );
            }
        }

        return self::joinPaths($configPath, $inputName);
    }

    private static function joinPaths(string ...$parts): string
    {
        $result = '';

        foreach ($parts as $part) {
            if (in_array($part, ['', '.', '..'], true)) {
                continue;
            }

            if ($result === '') {
                $result = rtrim($part, '/\\');
                continue;
            }

            $result .= DIRECTORY_SEPARATOR . trim($part, '/\\');
        }

        return $result;
    }

    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }
}
