<?php

namespace Davos\Graphy\ValueObjects;

use Davos\Graphy\Shared\Exceptions\ConfigException;

class GraphyConfig
{
    private string $path;
    private mixed $pathMapper;
    private string $driver;
    private int $permission;
    private bool $createDirectories;
    private int $directoryPermission;
    private string $timezone;

    public function __construct(array $config)
    {
        $this->setPath($config);
        $this->setPathMapper($config);
        $this->setDriver($config);
        $this->setPermission($config);
        $this->setCreateDirectories($config);
        $this->setDirectoryPermission($config);
        $this->setTimezone($config);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    private function setPath(array $config): void
    {
        if (!array_key_exists('path', $config) || !is_string($config['path'])) {
            throw ConfigException::invalidPath();
        }

        $this->path = $config['path'];
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    private function setDriver(array $config): void
    {
        if (!array_key_exists('driver', $config) || !is_string($config['driver'])) {
            throw ConfigException::missingDriver();
        }

        $allowedDrivers = ['ext'];
        if (!in_array($config['driver'], $allowedDrivers, true)) {
            throw ConfigException::unsupportedDriver($config['driver'], $allowedDrivers);
        }

        $this->driver = $config['driver'];
    }

    public function getPermission(): int
    {
        return $this->permission;
    }

    private function setPermission(array $config): void
    {
        if (!array_key_exists('permission', $config) || !is_int($config['permission'])) {
            throw ConfigException::invalidPermission();
        }

        $this->permission = $config['permission'];
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    private function setTimezone(array $config): void
    {
        if (!array_key_exists('timezone', $config) || !is_string($config['timezone'])) {
            throw ConfigException::missingTimezone();
        }

        $this->timezone = $config['timezone'];
    }

    public function getPathMapper(): mixed
    {
        return $this->pathMapper;
    }

    private function setPathMapper(array $config): void
    {
        if (
            !array_key_exists('path_mapper', $config)
            ||
            (!is_callable($config['path_mapper']) && !is_bool($config['path_mapper']))
        ) {
            throw ConfigException::invalidPathMapper();
        }

        $this->pathMapper = $config['path_mapper'];
    }

    public function getCreateDirectories(): bool
    {
        return $this->createDirectories;
    }

    private function setCreateDirectories(array $config): void
    {
        if (!array_key_exists('create_directories', $config) || !is_bool($config['create_directories'])) {
            throw ConfigException::invalidCreateDirectories();
        }

        $this->createDirectories = $config['create_directories'];
    }

    public function getDirectoryPermission(): int
    {
        return $this->directoryPermission;
    }

    private function setDirectoryPermission(array $config): void
    {
        if (!array_key_exists('directory_permission', $config) || !is_int($config['directory_permission'])) {
            throw ConfigException::invalidDirectoryPermission();
        }

        $this->directoryPermission = $config['directory_permission'];
    }
}
