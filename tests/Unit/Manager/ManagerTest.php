<?php

namespace Davos\Graphy\Tests\Unit\Manager;

use Davos\Graphy\Fetch\RrdSeries;
use Davos\Graphy\Manager\Drivers\DriverInterface;
use Davos\Graphy\Manager\Manager;
use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use Davos\Graphy\Shared\Exceptions\RrdToolExecutionException;
use Davos\Graphy\ValueObjects\GraphyConfig;
use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'graphy';

        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tmpDir);
    }

    public function testCreatePreparesFullPathCreatesDirectoriesAndCallsDriver(): void
    {
        $driver = $this->createMock(DriverInterface::class);

        $expectedFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'traffic.rrd';

        $driver
            ->expects($this->once())
            ->method('create')
            ->with(
                $expectedFile,
                ['create-options'],
                0644
            )
            ->willReturn(true);

        $manager = new Manager(
            $driver,
            $this->config($this->tmpDir)
        );

        $this->assertTrue(
            $manager->create('nested/traffic', ['create-options'])
        );

        $this->assertDirectoryExists(
            $this->tmpDir . DIRECTORY_SEPARATOR . 'nested'
        );
    }

    public function testCreateUsesPathMapperBeforeCallingDriver(): void
    {
        $driver = $this->createMock(DriverInterface::class);

        $expectedFile = $this->tmpDir
            . DIRECTORY_SEPARATOR
            . 'traffic'
            . DIRECTORY_SEPARATOR
            . 'traffic.rrd';

        $driver
            ->expects($this->once())
            ->method('create')
            ->with(
                $expectedFile,
                ['create-options'],
                0644
            )
            ->willReturn(true);

        $manager = new Manager(
            $driver,
            $this->config(
                $this->tmpDir,
                static fn (string $file): string => 'traffic/' . $file
            )
        );

        $this->assertTrue(
            $manager->create('traffic', ['create-options'])
        );

        $this->assertDirectoryExists(
            $this->tmpDir . DIRECTORY_SEPARATOR . 'traffic'
        );
    }

    public function testCreateThrowsWhenDirectoryIsMissingAndCreateDirectoriesIsDisabled(): void
    {
        $driver = $this->createMock(DriverInterface::class);

        $driver
            ->expects($this->never())
            ->method('create');

        $manager = new Manager(
            $driver,
            $this->config($this->tmpDir, false, false)
        );

        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('does not exist');

        $manager->create('missing/traffic', ['create-options']);
    }

    public function testCreateThrowsRrdToolExecutionExceptionWhenDriverFails(): void
    {
        $driver = $this->createMock(DriverInterface::class);

        $expectedFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'traffic.rrd';

        $driver
            ->expects($this->once())
            ->method('create')
            ->with(
                $expectedFile,
                ['create-options'],
                0644
            )
            ->willReturn('rrd create error');

        $manager = new Manager(
            $driver,
            $this->config($this->tmpDir)
        );

        $this->expectException(RrdToolExecutionException::class);

        $manager->create('traffic', ['create-options']);
    }

    public function testUpdatePreparesFullPathEnsuresFileExistsAndCallsDriver(): void
    {
        $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'traffic.rrd';
        touch($file);

        $driver = $this->createMock(DriverInterface::class);

        $driver
            ->expects($this->once())
            ->method('update')
            ->with(
                $file,
                ['update-options']
            )
            ->willReturn(true);

        $manager = new Manager(
            $driver,
            $this->config($this->tmpDir)
        );

        $this->assertTrue(
            $manager->update('traffic', ['update-options'])
        );
    }

    public function testUpdateThrowsWhenFileDoesNotExist(): void
    {
        $driver = $this->createMock(DriverInterface::class);

        $driver
            ->expects($this->never())
            ->method('update');

        $manager = new Manager(
            $driver,
            $this->config($this->tmpDir)
        );

        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('not found');

        $manager->update('missing', ['update-options']);
    }

    public function testUpdateThrowsRrdToolExecutionExceptionWhenDriverFails(): void
    {
        $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'traffic.rrd';
        touch($file);

        $driver = $this->createMock(DriverInterface::class);

        $driver
            ->expects($this->once())
            ->method('update')
            ->with(
                $file,
                ['update-options']
            )
            ->willReturn('rrd update error');

        $manager = new Manager(
            $driver,
            $this->config($this->tmpDir)
        );

        $this->expectException(RrdToolExecutionException::class);

        $manager->update('traffic', ['update-options']);
    }

    public function testFetchPreparesFullPathEnsuresFileExistsAndCallsDriver(): void
    {
        $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'traffic.rrd';
        touch($file);

        $series = $this->createStub(RrdSeries::class);

        $driver = $this->createMock(DriverInterface::class);

        $driver
            ->expects($this->once())
            ->method('fetch')
            ->with(
                $file,
                ['fetch-options']
            )
            ->willReturn($series);

        $manager = new Manager(
            $driver,
            $this->config($this->tmpDir)
        );

        $this->assertSame(
            $series,
            $manager->fetch('traffic', ['fetch-options'])
        );
    }

    public function testFetchThrowsWhenFileDoesNotExist(): void
    {
        $driver = $this->createMock(DriverInterface::class);

        $driver
            ->expects($this->never())
            ->method('fetch');

        $manager = new Manager(
            $driver,
            $this->config($this->tmpDir)
        );

        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('not found');

        $manager->fetch('missing', ['fetch-options']);
    }

    public function testFetchThrowsRrdToolExecutionExceptionWhenDriverFails(): void
    {
        $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'traffic.rrd';
        touch($file);

        $driver = $this->createMock(DriverInterface::class);

        $driver
            ->expects($this->once())
            ->method('fetch')
            ->with(
                $file,
                ['fetch-options']
            )
            ->willReturn('rrd fetch error');

        $manager = new Manager(
            $driver,
            $this->config($this->tmpDir)
        );

        $this->expectException(RrdToolExecutionException::class);

        $manager->fetch('traffic', ['fetch-options']);
    }

    public function testFirstPreparesFullPathEnsuresFileExistsAndCallsDriver(): void
    {
        $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'traffic.rrd';
        touch($file);

        $driver = $this->createMock(DriverInterface::class);

        $driver
            ->expects($this->once())
            ->method('first')
            ->with(
                $file,
                0
            )
            ->willReturn(1699920000);

        $manager = new Manager(
            $driver,
            $this->config($this->tmpDir)
        );

        $this->assertSame(
            1699920000,
            $manager->first('traffic', 0)
        );
    }

    public function testFirstThrowsWhenFileDoesNotExist(): void
    {
        $driver = $this->createMock(DriverInterface::class);

        $driver
            ->expects($this->never())
            ->method('first');

        $manager = new Manager(
            $driver,
            $this->config($this->tmpDir)
        );

        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('not found');

        $manager->first('missing', 0);
    }

    public function testFirstThrowsRrdToolExecutionExceptionWhenDriverFails(): void
    {
        $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'traffic.rrd';
        touch($file);

        $driver = $this->createMock(DriverInterface::class);

        $driver
            ->expects($this->once())
            ->method('first')
            ->with(
                $file,
                0
            )
            ->willReturn('rrd first error');

        $manager = new Manager(
            $driver,
            $this->config($this->tmpDir)
        );

        $this->expectException(RrdToolExecutionException::class);

        $manager->first('traffic', 0);
    }

    private function config(
        string $path,
        bool|callable $pathMapper = false,
        bool $createDirectories = true
    ): GraphyConfig {
        return new GraphyConfig([
            'path' => $path,
            'driver' => 'ext',
            'permission' => 0644,
            'directory_permission' => 0775,
            'create_directories' => $createDirectories,
            'path_mapper' => $pathMapper,
            'timezone' => 'UTC',
        ]);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            @unlink($path);
        }

        @rmdir($dir);
    }
}