<?php

namespace Davos\Graphy\Tests\Integration;

use Davos\Graphy\Fetch\Group\Interval\SecondInterval;
use Davos\Graphy\Manager\Factory\ManagerFactory;
use Davos\Graphy\Tests\Integration\Models\Traffic;
use PHPUnit\Framework\TestCase;

class TrafficTest extends TestCase
{
    private static int $start = 1699920000;
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

        $tmpDir = __DIR__ . DIRECTORY_SEPARATOR . 'Tmp';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        self::$fileName = $tmpDir . DIRECTORY_SEPARATOR . 'traffic.rrd';
        if (file_exists(self::$fileName)) {
            @unlink(self::$fileName);
        }

        Traffic::create(self::$fileName);

        $updates = [];
        $counter = 0;

        for ($rate = 1; $rate <= 3500; $rate++) {
            $counter += $rate * 10;
            $updates[self::$start + $rate] = ['traffic' => $counter];

            if (count($updates) === 500) {
                Traffic::update(self::$fileName, $updates);
                $updates = [];
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$fileName);
    }

    public function testFetchCounterRatesAtOneSecondResolution()
    {
        $data = Traffic::fetch(self::$fileName)
            ->average()
            ->resolution('1s')
            ->start(self::$start)
            ->end(self::$start + 30)
            ->run()
            ->get();

        $this->assertSame(
            [
                1699920002,
                1699920003,
                1699920004,
                1699920005,
                1699920006,
                1699920007,
                1699920008,
                1699920009,
                1699920010,
                1699920011,
                1699920012,
                1699920013,
                1699920014,
                1699920015,
                1699920016,
                1699920017,
                1699920018,
                1699920019,
                1699920020,
                1699920021,
                1699920022,
                1699920023,
                1699920024,
                1699920025,
                1699920026,
                1699920027,
                1699920028,
                1699920029,
                1699920030,
                1699920031,
            ],
            $data['timestamps']
        );

        $this->assertEqualsWithDelta(
            [
                20.0,
                30.0,
                40.0,
                50.0,
                60.0,
                70.0,
                80.0,
                90.0,
                100.0,
                110.0,
                120.0,
                130.0,
                140.0,
                150.0,
                160.0,
                170.0,
                180.0,
                190.0,
                200.0,
                210.0,
                220.0,
                230.0,
                240.0,
                250.0,
                260.0,
                270.0,
                280.0,
                290.0,
                300.0,
                310.0,
            ],
            $data['datasets']['traffic'],
            0.0000001,
        );
    }

    public function testFetchGroupsCounterRatesAcrossFiveSecondIntervals()
    {
        $data = Traffic::fetch(self::$fileName)
            ->average()
            ->resolution('1s')
            ->start(self::$start)
            ->end(self::$start + 30)
            ->run()
            ->group(SecondInterval::for(5))
            ->get();

        $this->assertSame(
            [
                1699920005,
                1699920010,
                1699920015,
                1699920020,
                1699920025,
                1699920030,
            ],
            $data['timestamps']
        );

        $this->assertEqualsWithDelta(
            [
                35.0,
                80.0,
                130.0,
                180.0,
                230.0,
                280.0,
            ],
            $data['datasets']['traffic'],
            0.0000001,
        );
    }

    public function testFetchCounterRatesAtTenSecondResolution()
    {
        $data = Traffic::fetch(self::$fileName)
            ->average()
            ->resolution('10s')
            ->start(self::$start)
            ->end(self::$start + 100)
            ->run()
            ->get();

        $this->assertSame(
            [
                1699920020,
                1699920030,
                1699920040,
                1699920050,
                1699920060,
                1699920070,
                1699920080,
                1699920090,
                1699920100,
                1699920110,
            ],
            $data['timestamps']
        );

        $this->assertEqualsWithDelta(
            [
                155.0,
                255.0,
                355.0,
                455.0,
                555.0,
                655.0,
                755.0,
                855.0,
                955.0,
                1055.0,
            ],
            $data['datasets']['traffic'],
            0.0000001,
        );
    }

    public function testFetchGroupsMaxCounterRatesAcrossFiveSecondIntervals(): void
    {
        $data = Traffic::fetch(self::$fileName)
            ->max()
            ->resolution('1s')
            ->start(self::$start)
            ->end(self::$start + 30)
            ->run()
            ->group(SecondInterval::for(5))
            ->get();

        $this->assertSame(
            [
                1699920005,
                1699920010,
                1699920015,
                1699920020,
                1699920025,
                1699920030,
            ],
            $data['timestamps']
        );

        $this->assertEqualsWithDelta(
            [
                50.0,
                100.0,
                150.0,
                200.0,
                250.0,
                300.0,
            ],
            $data['datasets']['traffic'],
            0.0000001,
        );
    }

    public function testFetchGroupsMinCounterRatesAcrossFiveSecondIntervals(): void
    {
        $data = Traffic::fetch(self::$fileName)
            ->min()
            ->resolution('1s')
            ->start(self::$start)
            ->end(self::$start + 30)
            ->run()
            ->group(SecondInterval::for(5))
            ->get();

        $this->assertSame(
            [
                1699920005,
                1699920010,
                1699920015,
                1699920020,
                1699920025,
                1699920030,
            ],
            $data['timestamps']
        );

        $this->assertEqualsWithDelta(
            [
                20.0,
                60.0,
                110.0,
                160.0,
                210.0,
                260.0,
            ],
            $data['datasets']['traffic'],
            0.0000001,
        );
    }

    public function testFetchCounterRatesAcrossChunks(): void
    {
        $data = Traffic::fetch(self::$fileName, 2)
            ->average()
            ->resolution('1s')
            ->start(self::$start)
            ->end(self::$start + 30)
            ->run()
            ->get();

        $this->assertSame(
            [
                1699920002,
                1699920003,
                1699920004,
                1699920005,
                1699920006,
                1699920007,
                1699920008,
                1699920009,
                1699920010,
                1699920011,
                1699920012,
                1699920013,
                1699920014,
                1699920015,
                1699920016,
                1699920017,
                1699920018,
                1699920019,
                1699920020,
                1699920021,
                1699920022,
                1699920023,
                1699920024,
                1699920025,
                1699920026,
                1699920027,
                1699920028,
                1699920029,
                1699920030,
                1699920031,
            ],
            $data['timestamps']
        );

        $this->assertEqualsWithDelta(
            [
                20.0, 30.0, 40.0, 50.0, 60.0,
                70.0, 80.0, 90.0, 100.0, 110.0,
                120.0, 130.0, 140.0, 150.0, 160.0,
                170.0, 180.0, 190.0, 200.0, 210.0,
                220.0, 230.0, 240.0, 250.0, 260.0,
                270.0, 280.0, 290.0, 300.0, 310.0,
            ],
            $data['datasets']['traffic'],
            0.0000001,
        );
    }
}
