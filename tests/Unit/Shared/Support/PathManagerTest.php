<?php

namespace Davos\Graphy\Tests\Unit\Shared\Support;

use Davos\Graphy\Shared\Support\PathManager;
use PHPUnit\Framework\TestCase;

class PathManagerTest extends TestCase
{
    public function testPreparesFullPathForSimpleFileName(): void
    {
        $this->assertSame(
            '/var/lib/rrd/traffic.rrd',
            PathManager::prepareFullPath('/var/lib/rrd', false, 'traffic')
        );
    }

    public function testPreparesFullPathForFileNameThatAlreadyHasRrdExtension(): void
    {
        $this->assertSame(
            '/var/lib/rrd/traffic.rrd',
            PathManager::prepareFullPath('/var/lib/rrd', false, 'traffic.rrd')
        );
    }

    public function testPreparesFullPathWhenConfigPathHasTrailingSlash(): void
    {
        $this->assertSame(
            '/var/lib/rrd/traffic.rrd',
            PathManager::prepareFullPath('/var/lib/rrd/', false, 'traffic.rrd')
        );
    }

    public function testPreparesFullPathForNestedRelativeFileName(): void
    {
        $this->assertSame(
            '/var/lib/rrd/1/traffic.rrd',
            PathManager::prepareFullPath('/var/lib/rrd', false, '1/traffic.rrd')
        );
    }

    public function testPreparesFullPathForDeepNestedRelativeFileName(): void
    {
        $this->assertSame(
            '/var/lib/rrd/customer/1/traffic.rrd',
            PathManager::prepareFullPath('/var/lib/rrd', false, 'customer/1/traffic.rrd')
        );
    }

    public function testAbsoluteInputPathIgnoresConfigPath(): void
    {
        $this->assertSame(
            '/tmp/traffic.rrd',
            PathManager::prepareFullPath('/var/lib/rrd', false, '/tmp/traffic.rrd')
        );
    }

    public function testAbsoluteInputPathWithoutExtensionGetsRrdExtension(): void
    {
        $this->assertSame(
            '/tmp/traffic.rrd',
            PathManager::prepareFullPath('/var/lib/rrd', false, '/tmp/traffic')
        );
    }

    public function testAppliesMapperToBaseName(): void
    {
        $this->assertSame(
            '/var/lib/rrd/traffic/traffic.rrd',
            PathManager::prepareFullPath(
                '/var/lib/rrd',
                static fn (string $file): string => 'traffic/' . $file,
                'traffic'
            )
        );
    }

    public function testAppliesMapperToBaseNameAndKeepsInputDirectory(): void
    {
        $this->assertSame(
            '/var/lib/rrd/traffic/customer/1/traffic.rrd',
            PathManager::prepareFullPath(
                '/var/lib/rrd',
                static fn (string $file): string => 'traffic/' . $file,
                'customer/1/traffic'
            )
        );
    }

    public function testMapperCanShardFileByFirstCharacter(): void
    {
        $this->assertSame(
            '/var/lib/rrd/t/traffic.rrd',
            PathManager::prepareFullPath(
                '/var/lib/rrd',
                static fn (string $file): string => $file[0] . '/' . $file,
                'traffic'
            )
        );
    }

    public function testEmptyConfigPathReturnsRelativePath(): void
    {
        $this->assertSame(
            'traffic.rrd',
            PathManager::prepareFullPath('', false, 'traffic')
        );
    }

    public function testEmptyConfigPathWithNestedFileReturnsNestedRelativePath(): void
    {
        $this->assertSame(
            '1/traffic.rrd',
            PathManager::prepareFullPath('', false, '1/traffic')
        );
    }

    public function testLeadingSlashNestedInputIsTreatedAsAbsolutePath(): void
    {
        $this->assertSame(
            '/1/traffic.rrd',
            PathManager::prepareFullPath('/var/lib/rrd', false, '/1/traffic.rrd')
        );
    }

    public function testLeadingSlashFileInputIsTreatedAsAbsolutePath(): void
    {
        $this->assertSame(
            '/traffic.rrd',
            PathManager::prepareFullPath('/var/lib/rrd', false, '/traffic.rrd')
        );
    }

    public function testLeadingSlashFileInputWithoutExtensionIsTreatedAsAbsolutePath(): void
    {
        $this->assertSame(
            '/traffic.rrd',
            PathManager::prepareFullPath('/var/lib/rrd', false, '/traffic')
        );
    }
}