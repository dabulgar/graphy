<?php

namespace Davos\Graphy\Tests\Unit\Fetch\Group\Interval;

use Davos\Graphy\Fetch\Group\Interval\YearInterval;
use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use PHPUnit\Framework\TestCase;

final class YearIntervalTest extends TestCase
{
    public function testCreatesOneYearIntervalInUtcByDefault(): void
    {
        $interval = new YearInterval();

        $timestamp = strtotime('2026-05-20 13:45:00 UTC');

        self::assertSame(
            [
                'start' => strtotime('2026-01-01 00:00:00 UTC'),
                'end' => strtotime('2027-01-01 00:00:00 UTC'),
            ],
            $interval->getInterval($timestamp)
        );
    }

    public function testCreatesMultipleYearIntervalInUtcByDefault(): void
    {
        $interval = YearInterval::for(2);

        $timestamp = strtotime('2026-05-20 13:45:00 UTC');

        self::assertSame(
            [
                'start' => strtotime('2026-01-01 00:00:00 UTC'),
                'end' => strtotime('2028-01-01 00:00:00 UTC'),
            ],
            $interval->getInterval($timestamp)
        );
    }

    public function testCreatesOneYearIntervalInEuropeSofia(): void
    {
        $interval = new YearInterval();

        $timestamp = strtotime('2026-05-20 13:45:00 UTC');

        self::assertSame(
            [
                // 2026-01-01 00:00 Europe/Sofia = 2025-12-31 22:00 UTC
                'start' => strtotime('2025-12-31 22:00:00 UTC'),

                // 2027-01-01 00:00 Europe/Sofia = 2026-12-31 22:00 UTC
                'end' => strtotime('2026-12-31 22:00:00 UTC'),
            ],
            $interval->getInterval($timestamp, 'Europe/Sofia')
        );
    }

    public function testReturnsSameYearWhenTimestampIsExactlyAtLocalYearStart(): void
    {
        $interval = new YearInterval();

        // 2026-01-01 00:00 Europe/Sofia
        $timestamp = strtotime('2025-12-31 22:00:00 UTC');

        self::assertSame(
            [
                'start' => strtotime('2025-12-31 22:00:00 UTC'),
                'end' => strtotime('2026-12-31 22:00:00 UTC'),
            ],
            $interval->getInterval($timestamp, 'Europe/Sofia')
        );
    }

    public function testReturnsNextYearWhenTimestampIsAtNextLocalYearStart(): void
    {
        $interval = new YearInterval();

        // 2027-01-01 00:00 Europe/Sofia
        $timestamp = strtotime('2026-12-31 22:00:00 UTC');

        self::assertSame(
            [
                'start' => strtotime('2026-12-31 22:00:00 UTC'),
                'end' => strtotime('2027-12-31 22:00:00 UTC'),
            ],
            $interval->getInterval($timestamp, 'Europe/Sofia')
        );
    }

    public function testHandlesLeapYearInUtc(): void
    {
        $interval = new YearInterval();

        $timestamp = strtotime('2024-05-20 13:45:00 UTC');

        $result = $interval->getInterval($timestamp);

        self::assertSame(
            [
                'start' => strtotime('2024-01-01 00:00:00 UTC'),
                'end' => strtotime('2025-01-01 00:00:00 UTC'),
            ],
            $result
        );

        self::assertSame(366 * 24 * 3600, $result['end'] - $result['start']);
    }

    public function testHandlesNonLeapYearInUtc(): void
    {
        $interval = new YearInterval();

        $timestamp = strtotime('2026-05-20 13:45:00 UTC');

        $result = $interval->getInterval($timestamp);

        self::assertSame(
            [
                'start' => strtotime('2026-01-01 00:00:00 UTC'),
                'end' => strtotime('2027-01-01 00:00:00 UTC'),
            ],
            $result
        );

        self::assertSame(365 * 24 * 3600, $result['end'] - $result['start']);
    }

    public function testThrowsExceptionWhenIntervalIsZero(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Interval must be greater than zero.');

        YearInterval::for(0);
    }

    public function testThrowsExceptionWhenIntervalIsNegative(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Interval must be greater than zero.');

        YearInterval::for(-1);
    }
}
