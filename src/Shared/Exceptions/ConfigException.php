<?php

namespace Davos\Graphy\Shared\Exceptions;

class ConfigException extends GraphyException
{
    public const int PATH_INVALID = 1001;
    public const int DRIVER_INVALID = 1002;
    public const int DRIVER_UNSUPPORTED = 1003;
    public const int PERMISSION_INVALID = 1004;
    public const int TIMEZONE_INVALID = 1005;
    public const int PATH_MAPPER_INVALID = 1006;
    public const int CREATE_DIRECTORIES_INVALID = 1007;
    public const int DIRECTORY_PERMISSION_INVALID = 1008;

    public static function invalidPath(): self
    {
        return new self('The "path" config value must be provided and must be a string.');
    }

    public static function missingDriver(): self
    {
        return new self('The "driver" config value must be provided and must be a string.');
    }

    public static function unsupportedDriver(string $driver, array $allowedDrivers): self
    {
        return new self(sprintf(
            'Unsupported driver "%s". Allowed drivers are: %s.',
            $driver,
            implode(', ', $allowedDrivers)
        ));
    }

    public static function invalidPermission(): self
    {
        return new self('The "permission" config value must be provided and must be a int.');
    }

    public static function missingTimezone(): self
    {
        return new self('The "timezone" config value must be provided and must be a string.');
    }

    public static function invalidPathMapper(): self
    {
        return new self('The "path_mapper" config value must be provided and must be a callable or a bool.');
    }

    public static function invalidCreateDirectories(): self
    {
        return new self('The "create_directories" config value must be provided and must be a bool.');
    }

    public static function invalidDirectoryPermission(): self
    {
        return new self('The "directory_permission" config value must be provided and must be a int.');
    }
}
