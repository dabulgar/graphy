<?php

namespace Davos\Graphy\Manager\Factory;

use Davos\Graphy\Manager\Drivers\ExtensionDriver;
use Davos\Graphy\Manager\Manager;
use Davos\Graphy\ValueObjects\GraphyConfig;

final class ManagerFactory
{
    private static ?GraphyConfig $config = null;

    private static ?Manager $manager = null;

    public static function configure(array $config): void
    {
        self::$config = new GraphyConfig($config);
    }

    public static function make(): Manager
    {
        if (self::$manager !== null) {
            return self::$manager;
        }

        if (is_null(self::$config)) {
            throw new \RuntimeException('Graphy has not been configured. Call ManagerFactory::configure($config) before ManagerFactory::make().');
        }

        $driver = match (self::$config->getDriver()) {
            'ext' => new ExtensionDriver(),
            default => throw new \RuntimeException('Unsupported driver: ' . self::$config->getDriver()),
        };

        self::$manager = new Manager($driver, self::$config);

        return self::$manager;
    }
}
