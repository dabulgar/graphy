<?php

namespace Davos\Graphy\Tests\Integration;

use Davos\Graphy\Fetch\Group\Interval\SecondInterval;
use Davos\Graphy\Manager\Factory\ManagerFactory;
use Davos\Graphy\Tests\Integration\Models\CpuLoad;
use PHPUnit\Framework\TestCase;

class CpuLoadTest extends TestCase
{
    private static int $start = 1700000000;
    private static string $fileName;

    public static function setUpBeforeClass(): void
    {
        $config = [
            'path' => '',
            'path_mapper' => false,
            'driver' => 'ext',
            'permission' => 0644,
            'create_directories' => true,
            'directory_permission' => 0775,
            'timezone' => 'UTC',
        ];

        ManagerFactory::configure($config);

        self::$fileName = __DIR__ . DIRECTORY_SEPARATOR . 'Tmp/cpu_load.rrd';
        if (file_exists(self::$fileName)) {
            @unlink(self::$fileName);
        }

        CpuLoad::create(static::$fileName);

        CpuLoad::update(static::$fileName,
            [
                self::$start + 1 => ['cpu_1' => 10, 'cpu_2' => 10, 'cpu_3' => 10],
                self::$start + 2 => ['cpu_1' => 20, 'cpu_2' => 10, 'cpu_3' => 10],
                self::$start + 3 => ['cpu_1' => 30, 'cpu_2' => 10, 'cpu_3' => 10],
                self::$start + 4 => ['cpu_1' => 40, 'cpu_2' => 10, 'cpu_3' => 10],
                self::$start + 5 => ['cpu_1' => 50, 'cpu_2' => 10, 'cpu_3' => 10],
                self::$start + 6 => ['cpu_1' => 60, 'cpu_2' => 10, 'cpu_3' => 10],
                self::$start + 7 => ['cpu_1' => 70, 'cpu_2' => 10, 'cpu_3' => 10],
                self::$start + 8 => ['cpu_1' => 60, 'cpu_2' => 10, 'cpu_3' => 10],
                self::$start + 9 => ['cpu_1' => 50, 'cpu_2' => 10, 'cpu_3' => 10],
                self::$start + 10 => ['cpu_1' => 40, 'cpu_2' => 10, 'cpu_3' => 10],
                self::$start + 11 => ['cpu_1' => 30, 'cpu_2' => 10, 'cpu_3' => 10],
                self::$start + 12 => ['cpu_1' => 60, 'cpu_2' => 10, 'cpu_3' => 10],
                self::$start + 13 => ['cpu_1' => 10, 'cpu_2' => 10, 'cpu_3' => 10],
                self::$start + 14 => ['cpu_1' => 50, 'cpu_2' => 10, 'cpu_3' => 10],
                self::$start + 15 => ['cpu_1' => 80, 'cpu_2' => 10, 'cpu_3' => 10],
            ]
        );
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$fileName);
    }

    public function testFetchReturnsExpectedValues()
    {
        $start = self::$start;
        $end = self::$start + 5;

        $data = CpuLoad::fetch(self::$fileName)
            ->average()
            ->resolution('1s')
            ->start($start)
            ->end($end)
            ->run()
            ->get();

        $this->assertSame(
            [
                self::$start + 2, // safe margin applied
                self::$start + 3,
                self::$start + 4,
                self::$start + 5,
                self::$start + 6,
            ],
            $data['timestamps'],
        );

        $this->assertEqualsWithDelta(
            [20.0, 30.0, 40.0, 50.0, 60.0],
            $data['datasets']['cpu_1'],
            0.000001
        );

        $this->assertEqualsWithDelta(
            [10.0, 10.0, 10.0, 10.0, 10.0],
            $data['datasets']['cpu_2'],
            0.000001
        );

        $this->assertEqualsWithDelta(
            [10.0, 10.0, 10.0, 10.0, 10.0],
            $data['datasets']['cpu_3'],
            0.000001
        );
    }

    public function testFetchMergesChunks()
    {
        $start = self::$start;
        $end = self::$start + 11;

        $data = CpuLoad::fetch(self::$fileName, 2)
            ->average()
            ->resolution('1s')
            ->start($start)
            ->end($end)
            ->run()
            ->get();

        $this->assertSame(
            [
                self::$start + 2, // safe margin applied
                self::$start + 3,
                self::$start + 4,
                self::$start + 5,
                self::$start + 6,
                self::$start + 7,
                self::$start + 8,
                self::$start + 9,
                self::$start + 10,
                self::$start + 11,
                self::$start + 12,
            ],
            $data['timestamps'],
        );

        $this->assertEqualsWithDelta(
            [20.0, 30.0, 40.0, 50.0, 60.0, 70.0, 60.0, 50.0, 40.0, 30.0, 60.0],
            $data['datasets']['cpu_1'],
            0.000001
        );
    }

    public function testFetchGroupsValuesAcrossChunks()
    {
        $start = self::$start;
        $end = self::$start + 16;

        $data = CpuLoad::fetch(self::$fileName, 2)
            ->average()
            ->resolution('1s')
            ->start($start)
            ->end($end)
            ->run()
            ->group(SecondInterval::for(5))
            ->get();

        $this->assertSame(
            [
                self::$start + 5,
                self::$start + 10,
                self::$start + 15,
            ],
            $data['timestamps'],
        );

        $this->assertEqualsWithDelta(
            [35.0, 56.0, 46.0],
            $data['datasets']['cpu_1'],
            0.000001
        );
    }

    public function testLabelsUseUtcTimezone()
    {
        $start = self::$start;
        $end = self::$start + 16;

        $data = CpuLoad::fetch(self::$fileName, 2)
            ->average()
            ->resolution('1s')
            ->start($start)
            ->end($end)
            ->run()
            ->labels(SecondInterval::for(5),  'Y-m-d H:i:s')
            ->get();

        $this->assertSame(
            [
                self::$start + 2,
                self::$start + 3,
                self::$start + 4,
                '2023-11-14 22:13:25',
                self::$start + 6,
                self::$start + 7,
                self::$start + 8,
                self::$start + 9,
                '2023-11-14 22:13:30',
                self::$start + 11,
                self::$start + 12,
                self::$start + 13,
                self::$start + 14,
                '2023-11-14 22:13:35',
                self::$start + 16,
                self::$start + 17,
            ],
            $data['timestamps'],
        );
    }

    public function testLabelsUseConfiguredTimezone()
    {
        $start = self::$start;
        $end = self::$start + 16;

        $data = CpuLoad::fetch(self::$fileName, 2)
            ->average()
            ->resolution('1s')
            ->start($start)
            ->end($end)
            ->run()
            ->timezone('Europe/Sofia')
            ->labels(SecondInterval::for(5), 'Y-m-d H:i:s')
            ->get();

        $this->assertSame(
            [
                self::$start + 2,
                self::$start + 3,
                self::$start + 4,
                '2023-11-15 00:13:25',
                self::$start + 6,
                self::$start + 7,
                self::$start + 8,
                self::$start + 9,
                '2023-11-15 00:13:30',
                self::$start + 11,
                self::$start + 12,
                self::$start + 13,
                self::$start + 14,
                '2023-11-15 00:13:35',
                self::$start + 16,
                self::$start + 17,
            ],
            $data['timestamps'],
        );
    }

    public function testLabelsCanKeepNullForNonBoundaryTimestamps()
    {
        $start = self::$start;
        $end = self::$start + 16;

        $data = CpuLoad::fetch(self::$fileName, 2)
            ->average()
            ->resolution('1s')
            ->start($start)
            ->end($end)
            ->run()
            ->labels(SecondInterval::for(5), 'Y-m-d H:i:s', null)
            ->get();

        $this->assertSame(
            [
                null,
                null,
                null,
                '2023-11-14 22:13:25',
                null,
                null,
                null,
                null,
                '2023-11-14 22:13:30',
                null,
                null,
                null,
                null,
                '2023-11-14 22:13:35',
                null,
                null,
            ],
            $data['timestamps'],
        );
    }

    public function testLabelsCanUseCustomNonStringNonBoundaryValue()
    {
        $start = self::$start;
        $end = self::$start + 16;

        $data = CpuLoad::fetch(self::$fileName, 2)
            ->average()
            ->resolution('1s')
            ->start($start)
            ->end($end)
            ->run()
            ->labels(SecondInterval::for(5), 'Y-m-d H:i:s', false)
            ->get();

        $this->assertSame(
            [
                false,
                false,
                false,
                '2023-11-14 22:13:25',
                false,
                false,
                false,
                false,
                '2023-11-14 22:13:30',
                false,
                false,
                false,
                false,
                '2023-11-14 22:13:35',
                false,
                false,
            ],
            $data['timestamps'],
        );
    }

    public function testLabelsCanFormatNonBoundaryTimestamps()
    {
        $start = self::$start;
        $end = self::$start + 16;

        $data = CpuLoad::fetch(self::$fileName, 2)
            ->average()
            ->resolution('1s')
            ->start($start)
            ->end($end)
            ->run()
            ->timezone('Europe/Sofia')
            ->labels(SecondInterval::for(5), 'H:i:s', 'Y-m-d H:i:s')
            ->get();

        $this->assertSame(
            [
                '2023-11-15 00:13:22',
                '2023-11-15 00:13:23',
                '2023-11-15 00:13:24',
                '00:13:25',
                '2023-11-15 00:13:26',
                '2023-11-15 00:13:27',
                '2023-11-15 00:13:28',
                '2023-11-15 00:13:29',
                '00:13:30',
                '2023-11-15 00:13:31',
                '2023-11-15 00:13:32',
                '2023-11-15 00:13:33',
                '2023-11-15 00:13:34',
                '00:13:35',
                '2023-11-15 00:13:36',
                '2023-11-15 00:13:37',
            ],
            $data['timestamps'],
        );
    }

    public function testFetchUsesMaxArchiveForThreeSecondResolution()
    {
        $start = self::$start;
        $end = self::$start + 10;

        $data = CpuLoad::fetch(self::$fileName)
            ->max()
            ->resolution('3s')
            ->start($start)
            ->end($end)
            ->run()
            ->get();

        $this->assertSame(
            [
                self::$start + 4, // safe margin applied
                self::$start + 7,
                self::$start + 10,
                self::$start + 13,
            ],
            $data['timestamps'],
        );

        $this->assertEqualsWithDelta(
            [40.0, 70.0, 60.0, 60.0],
            $data['datasets']['cpu_1'],
            0.000001
        );
    }

    public function testFetchUsesMinArchiveForThreeSecondResolution()
    {
        $start = self::$start;
        $end = self::$start + 10;

        $data = CpuLoad::fetch(self::$fileName)
            ->min()
            ->resolution('3s')
            ->start($start)
            ->end($end)
            ->run()
            ->get();

        $this->assertSame(
            [
                self::$start + 4, // safe margin applied
                self::$start + 7,
                self::$start + 10,
                self::$start + 13,
            ],
            $data['timestamps'],
        );

        $this->assertEqualsWithDelta(
            [20.0, 50.0, 40.0, 10.0],
            $data['datasets']['cpu_1'],
            0.000001
        );
    }

    public function testFetchGroupsMaxValuesAcrossFiveSecondIntervals()
    {
        $start = self::$start;
        $end = self::$start + 15;

        $data = CpuLoad::fetch(self::$fileName)
            ->max()
            ->resolution('1s')
            ->start($start)
            ->end($end)
            ->run()
            ->group(SecondInterval::for(5))
            ->get();

        $this->assertSame(
            [
                self::$start + 5, // safe margin applied
                self::$start + 10,
                self::$start + 15,
            ],
            $data['timestamps'],
        );

        $this->assertEqualsWithDelta(
            [50.0, 70.0, 80.0],
            $data['datasets']['cpu_1'],
            0.000001
        );
    }

    public function testFetchConvertsUnknownValuesToNull()
    {
        $start = self::$start;
        $end = self::$start + 20;

        $data = CpuLoad::fetch(self::$fileName, 2)
            ->average()
            ->resolution('1s')
            ->start($start)
            ->end($end)
            ->run()
            ->get();

        $this->assertSame(
            [
                self::$start + 2,
                self::$start + 3,
                self::$start + 4,
                self::$start + 5,
                self::$start + 6,
                self::$start + 7,
                self::$start + 8,
                self::$start + 9,
                self::$start + 10,
                self::$start + 11,
                self::$start + 12,
                self::$start + 13,
                self::$start + 14,
                self::$start + 15,
                self::$start + 16,
                self::$start + 17,
                self::$start + 18,
                self::$start + 19,
                self::$start + 20,
                self::$start + 21,
            ],
            $data['timestamps'],
        );

        $this->assertSame(
            [20.0, 30.0, 40.0, 50.0, 60.0, 70.0, 60.0, 50.0, 40.0, 30.0, 60.0, 10.0, 50.0, 80.0, null, null, null, null, null, null],
            $data['datasets']['cpu_1']
        );
    }

    public function testGroupedFetchConvertsUnknownGroupsToNull()
    {
        $start = self::$start;
        $end = self::$start + 39;

        $data = CpuLoad::fetch(self::$fileName)
            ->max()
            ->resolution('1s')
            ->start($start)
            ->end($end)
            ->run()
            ->group(SecondInterval::for(5))
            ->get();

        $this->assertSame(
            [
                self::$start + 5,
                self::$start + 10,
                self::$start + 15,
                self::$start + 20,
                self::$start + 25,
                self::$start + 30,
                self::$start + 35,
                self::$start + 40,
            ],
            $data['timestamps'],
        );

        $this->assertSame(
            [50.0, 70.0, 80.0, null, null, null, null, null],
            $data['datasets']['cpu_1']
        );
    }
}
