<?php

namespace Davos\Graphy\Tests\Unit\Fetch;

use Davos\Graphy\Fetch\RrdSeries;
use PHPUnit\Framework\TestCase;

class RrdSeriesTest extends TestCase
{
    public function testCreatesSeriesFromExtensionResponse(): void
    {
        $response = [
            'start' => 1710000000,
            'step' => 300,
            'data' => [
                'cpu' => [
                    1710000300 => 10.5,
                    1710000600 => 11.2,
                    1710000900 => 12.0,
                ],
                'memory' => [
                    1710000300 => 100.0,
                    1710000600 => 110.0,
                    1710000900 => 120.0,
                ],
            ],
        ];

        $series = RrdSeries::fromExtensionResponse($response);

        self::assertSame(1710000000, $series->start);
        self::assertSame(300, $series->step);

        self::assertSame(
            ['cpu', 'memory'],
            $series->dataSources
        );

        self::assertSame(
            [
                'cpu' => [10.5, 11.2, 12.0],
                'memory' => [100.0, 110.0, 120.0],
            ],
            $series->datasets
        );
    }

    public function testTimestampAtCalculatesTimestampFromStartAndStep(): void
    {
        $series = new RrdSeries();

        $series->start = 1710000000;
        $series->step = 300;

        self::assertSame(1710000000, $series->timestampAt(0));
        self::assertSame(1710000300, $series->timestampAt(1));
        self::assertSame(1710000600, $series->timestampAt(2));
    }

    public function testAddDataset(): void
    {
        $series = new RrdSeries();

        $series->addDataset('load', [0.5, 0.7, 0.9]);

        self::assertSame(
            [
                'load' => [0.5, 0.7, 0.9],
            ],
            $series->datasets
        );
    }

    public function testShiftFirstElementRemovesFirstValueFromEveryDatasetAndMovesStartForward(): void
    {
        $series = new RrdSeries();

        $series->start = 1710000000;
        $series->step = 300;

        $series->addDataset('cpu', [10.5, 11.2, 12.0]);
        $series->addDataset('memory', [100.0, 110.0, 120.0]);

        $series->shiftFirstElement();

        self::assertSame(1710000300, $series->start);

        self::assertSame(
            [
                'cpu' => [11.2, 12.0],
                'memory' => [110.0, 120.0],
            ],
            $series->datasets
        );
    }

    public function testIteratorYieldsRowsByTimestamp(): void
    {
        $response = [
            'start' => 1710000000,
            'step' => 300,
            'data' => [
                'cpu' => [
                    1710000300 => 10.5,
                    1710000600 => 11.2,
                    1710000900 => 12.0,
                ],
                'memory' => [
                    1710000300 => 100.0,
                    1710000600 => 110.0,
                    1710000900 => 120.0,
                ],
            ],
        ];

        $series = RrdSeries::fromExtensionResponse($response);

        self::assertSame(
            [
                1710000300 => [
                    'cpu' => 10.5,
                    'memory' => 100.0,
                ],
                1710000600 => [
                    'cpu' => 11.2,
                    'memory' => 110.0,
                ],
                1710000900 => [
                    'cpu' => 12.0,
                    'memory' => 120.0,
                ],
            ],
            iterator_to_array($series)
        );
    }

    public function testIteratorAfterShiftFirstElement(): void
    {
        $response = [
            'start' => 1710000000,
            'step' => 300,
            'data' => [
                'cpu' => [
                    1710000300 => 10.5,
                    1710000600 => 11.2,
                    1710000900 => 12.0,
                ],
                'memory' => [
                    1710000300 => 100.0,
                    1710000600 => 110.0,
                    1710000900 => 120.0,
                ],
            ],
        ];

        $series = RrdSeries::fromExtensionResponse($response);

        $series->shiftFirstElement();

        self::assertSame(
            [
                1710000600 => [
                    'cpu' => 11.2,
                    'memory' => 110.0,
                ],
                1710000900 => [
                    'cpu' => 12.0,
                    'memory' => 120.0,
                ],
            ],
            iterator_to_array($series)
        );
    }
}
