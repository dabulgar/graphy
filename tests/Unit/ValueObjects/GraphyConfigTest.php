<?php

namespace Davos\Graphy\Tests\Unit\ValueObjects;

use Davos\Graphy\Shared\Exceptions\ConfigException;
use Davos\Graphy\ValueObjects\GraphyConfig;
use PHPUnit\Framework\TestCase;

class GraphyConfigTest extends TestCase
{
    public function testCreatesConfigFromValidArray(): void
    {
        $pathMapper = static fn (string $path): string => '/mapped/' . $path;

        $config = new GraphyConfig($this->validConfig([
            'path_mapper' => $pathMapper,
        ]));

        $this->assertSame('/Tmp/rrd', $config->getPath());
        $this->assertSame($pathMapper, $config->getPathMapper());
        $this->assertSame('ext', $config->getDriver());
        $this->assertSame(0644, $config->getPermission());
        $this->assertTrue($config->getCreateDirectories());
        $this->assertSame(0775, $config->getDirectoryPermission());
        $this->assertSame('UTC', $config->getTimezone());
    }

    public function testThrowsExceptionWhenPathIsMissing(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The "path" config value must be provided and must be a string.');

        new GraphyConfig($this->validConfigWithout('path'));
    }

    public function testThrowsExceptionWhenPathIsNotAString(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The "path" config value must be provided and must be a string.');

        new GraphyConfig($this->validConfig(['path' => 123]));
    }

    public function testThrowsExceptionWhenDriverIsMissing(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The "driver" config value must be provided and must be a string.');

        new GraphyConfig($this->validConfigWithout('driver'));
    }

    public function testThrowsExceptionWhenDriverIsNotAString(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The "driver" config value must be provided and must be a string.');

        new GraphyConfig($this->validConfig(['driver' => 123]));
    }

    public function testThrowsExceptionWhenDriverIsNotSupported(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(sprintf(
            'Unsupported driver "%s". Allowed drivers are: %s.',
            'not-exist',
            implode(', ', ['ext'])
        ));

        new GraphyConfig($this->validConfig(['driver' => 'not-exist']));
    }

    public function testThrowsExceptionWhenPermissionIsMissing(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The "permission" config value must be provided and must be a int.');

        new GraphyConfig($this->validConfigWithout('permission'));
    }

    public function testThrowsExceptionWhenPermissionIsNotAnInt(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The "permission" config value must be provided and must be a int.');

        new GraphyConfig($this->validConfig(['permission' => '0644']));
    }

    public function testPermissionIsStoredAsIntegerEvenIfPrintedAsDecimal(): void
    {
        $config = new GraphyConfig($this->validConfig());

        $this->assertSame(420, $config->getPermission());
        $this->assertSame('644', sprintf('%o', $config->getPermission()));
    }

    public function testThrowsExceptionWhenTimezoneIsMissing(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The "timezone" config value must be provided and must be a string.');

        new GraphyConfig($this->validConfigWithout('timezone'));
    }

    public function testThrowsExceptionWhenTimezoneIsNotAString(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The "timezone" config value must be provided and must be a string.');

        new GraphyConfig($this->validConfig(['timezone' => 123]));
    }

    public function testAcceptsFalsePathMapper(): void
    {
        $config = new GraphyConfig($this->validConfig(['path_mapper' => false]));

        $this->assertFalse($config->getPathMapper());
    }

    public function testAcceptsTruePathMapper(): void
    {
        $config = new GraphyConfig($this->validConfig(['path_mapper' => true]));

        $this->assertTrue($config->getPathMapper());
    }

    public function testThrowsExceptionWhenPathMapperIsMissing(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The "path_mapper" config value must be provided and must be a callable or a bool.');

        new GraphyConfig($this->validConfigWithout('path_mapper'));
    }

    public function testThrowsExceptionWhenPathMapperIsInvalid(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The "path_mapper" config value must be provided and must be a callable or a bool.');

        new GraphyConfig($this->validConfig(['path_mapper' => 'not-callable']));
    }

    public function testThrowsExceptionWhenCreateDirectoriesIsMissing(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The "create_directories" config value must be provided and must be a bool.');

        new GraphyConfig($this->validConfigWithout('create_directories'));
    }

    public function testThrowsExceptionWhenCreateDirectoriesIsNotABool(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The "create_directories" config value must be provided and must be a bool.');

        new GraphyConfig($this->validConfig(['create_directories' => 1]));
    }

    public function testThrowsExceptionWhenDirectoryPermissionIsMissing(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The "directory_permission" config value must be provided and must be a int.');

        new GraphyConfig($this->validConfigWithout('directory_permission'));
    }

    public function testThrowsExceptionWhenDirectoryPermissionIsNotAnInt(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The "directory_permission" config value must be provided and must be a int.');

        new GraphyConfig($this->validConfig(['directory_permission' => '0775']));
    }

    public function testDirectoryPermissionIsStoredSeparatelyFromFilePermission(): void
    {
        $config = new GraphyConfig($this->validConfig([
            'permission' => 0644,
            'directory_permission' => 0775,
        ]));

        $this->assertSame(0644, $config->getPermission());
        $this->assertSame(0775, $config->getDirectoryPermission());
        $this->assertSame('644', sprintf('%o', $config->getPermission()));
        $this->assertSame('775', sprintf('%o', $config->getDirectoryPermission()));
    }

    private function validConfig(array $overrides = []): array
    {
        return array_replace([
            'path' => '/Tmp/rrd',
            'path_mapper' => false,
            'driver' => 'ext',
            'permission' => 0644,
            'create_directories' => true,
            'directory_permission' => 0775,
            'timezone' => 'UTC',
        ], $overrides);
    }

    private function validConfigWithout(string $key): array
    {
        $config = $this->validConfig();
        unset($config[$key]);

        return $config;
    }
}
