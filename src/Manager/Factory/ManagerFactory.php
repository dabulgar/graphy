<?php

namespace Davos\Graphy\Manager\Factory;

use Davos\Graphy\Manager\Drivers\ExtensionDriver;
use Davos\Graphy\Manager\Manager;
use Davos\Graphy\ValueObjects\GraphyConfig;

class ManagerFactory
{
    private static ?GraphyConfig $config = null;

    private static ?Manager $manager = null;

    public static function configure(array $config): void
    {
        static::$config = new GraphyConfig($config);
    }

    public static function make(): Manager
    {
        if (static::$manager !== null) {
            return static::$manager;
        }

        if (is_null(static::$config)) {
            throw new \RuntimeException('Graphy has not been configured. Call ManagerFactory::configure($config) before ManagerFactory::make().');
        }

        $driver = match (static::$config->getDriver()) {
            'ext' => new ExtensionDriver(),
        };

        static::$manager = new Manager($driver, static::$config);

        return static::$manager;
    }
}