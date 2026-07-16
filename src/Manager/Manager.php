<?php

namespace Davos\Graphy\Manager;

use Davos\Graphy\Fetch\RrdSeries;
use Davos\Graphy\Manager\Drivers\DriverInterface;
use Davos\Graphy\Shared\Exceptions\RrdToolExecutionException;
use Davos\Graphy\Shared\Support\FileManager;
use Davos\Graphy\Shared\Support\PathManager;
use Davos\Graphy\ValueObjects\GraphyConfig;

class Manager
{
    private DriverInterface $driver;

    public GraphyConfig $config;

    public function __construct(DriverInterface $driver, GraphyConfig $config)
    {
        $this->driver = $driver;

        $this->config = $config;
    }

    /**
     * @param string $file
     * @param array $options
     * @return true
     * @throws RrdToolExecutionException
     */
    public function create(string $file, array $options): true
    {
        $rrdFileName = PathManager::prepareFullPath($this->config->getPath(), $this->config->getPathMapper(), $file);

        PathManager::ensureDirectories($rrdFileName, $this->config->getCreateDirectories(), $this->config->getDirectoryPermission());

        $response = $this->driver->create(
            $rrdFileName,
            $options,
            $this->config->getPermission()
        );

        if ($response === true) {
            return true;
        }

        throw new RrdToolExecutionException(
            rrdError: $response,
            fileName: $rrdFileName,
            action: 'create',
            options: $options,
        );
    }

    /**
     * @param string $file
     * @param array $options
     * @return bool
     * @throws RrdToolExecutionException
     */
    public function update(string $file, array $options): bool
    {
        $rrdFileName = PathManager::prepareFullPath($this->config->getPath(), $this->config->getPathMapper(), $file);

        FileManager::ensureFileExists($rrdFileName);

        $response = $this->driver->update(
            $rrdFileName,
            $options
        );

        if ($response === true) {
            return true;
        }

        throw new RrdToolExecutionException(
            rrdError: $response,
            fileName: $rrdFileName,
            action: 'update',
            options: $options,
        );
    }

    /**
     * @param string $file
     * @param array $options
     * @return RrdSeries
     */
    public function fetch(string $file, array $options): RrdSeries
    {
        $rrdFileName = PathManager::prepareFullPath($this->config->getPath(), $this->config->getPathMapper(), $file);

        FileManager::ensureFileExists($rrdFileName);

        $response = $this->driver->fetch(
            $rrdFileName,
            $options
        );

        if ($response instanceof RrdSeries) {
            return $response;
        }

        throw new RrdToolExecutionException(
            rrdError: $response,
            fileName: $rrdFileName,
            action: 'fetch',
            options: $options,
        );
    }

    public function first(string $file, int $index): int
    {
        $rrdFileName = PathManager::prepareFullPath($this->config->getPath(), $this->config->getPathMapper(), $file);

        FileManager::ensureFileExists($rrdFileName);

        $response = $this->driver->first(
            $rrdFileName,
            $index
        );

        if (is_int($response)) {
            return $response;
        }

        throw new RrdToolExecutionException(
            rrdError: $response,
            fileName: $rrdFileName,
            action: 'first',
            options: ['index' => $index],
        );
    }
}
