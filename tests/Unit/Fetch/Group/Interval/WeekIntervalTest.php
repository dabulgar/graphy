<?php

namespace Davos\Graphy\Tests\Unit\Fetch\Group\Interval;

use Davos\Graphy\Fetch\Group\Interval\WeekInterval;
use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use PHPUnit\Framework\TestCase;

final class WeekIntervalTest extends TestCase
{
    public function testCreatesOneWeekIntervalInUtcByDefault(): void
    {
        $interval = new WeekInterval();

        // Wednesday
        $timestamp = strtotime('2026-05-20 13:45:00 UTC');

        self::assertSame(
            [
                'start' => strtotime('2026-05-18 00:00:00 UTC'),
                'end' => strtotime('2026-05-25 00:00:00 UTC'),
            ],
            $interval->getInterval($timestamp)
        );
    }

    public function testCreatesMultipleWeekIntervalInUtcByDefault(): void
    {
        $interval = WeekInterval::for(2);

        // Wednesday
        $timestamp = strtotime('2026-05-20 13:45:00 UTC');

        self::assertSame(
            [
                'start' => strtotime('2026-05-18 00:00:00 UTC'),
                'end' => strtotime('2026-06-01 00:00:00 UTC'),
            ],
            $interval->getInterval($timestamp)
        );
    }

    public function testCreatesOneWeekIntervalInEuropeSofiaSummerTime(): void
    {
        $interval = new WeekInterval();

        // Wednesday, 2026-05-20 16:45 Europe/Sofia
        $timestamp = strtotime('2026-05-20 13:45:00 UTC');

        self::assertSame(
            [
                // Monday 2026-05-18 00:00 Europe/Sofia = 2026-05-17 21:00 UTC
                'start' => strtotime('2026-05-17 21:00:00 UTC'),

                // Monday 2026-05-25 00:00 Europe/Sofia = 2026-05-24 21:00 UTC
                'end' => strtotime('2026-05-24 21:00:00 UTC'),
            ],
            $interval->getInterval($timestamp, 'Europe/Sofia')
        );
    }

    public function testCreatesOneWeekIntervalInEuropeSofiaWinterTime(): void
    {
        $interval = new WeekInterval();

        // Tuesday, 2026-01-20 15:45 Europe/Sofia
        $timestamp = strtotime('2026-01-20 13:45:00 UTC');

        self::assertSame(
            [
                // Monday 2026-01-19 00:00 Europe/Sofia = 2026-01-18 22:00 UTC
                'start' => strtotime('2026-01-18 22:00:00 UTC'),

                // Monday 2026-01-26 00:00 Europe/Sofia = 2026-01-25 22:00 UTC
                'end' => strtotime('2026-01-25 22:00:00 UTC'),
            ],
            $interval->getInterval($timestamp, 'Europe/Sofia')
        );
    }

    public function testReturnsSameWeekWhenTimestampIsExactlyAtLocalMondayStart(): void
    {
        $interval = new WeekInterval();

        // Monday 2026-05-18 00:00 Europe/Sofia
        $timestamp = strtotime('2026-05-17 21:00:00 UTC');

        self::assertSame(
            [
                'start' => strtotime('2026-05-17 21:00:00 UTC'),
                'end' => strtotime('2026-05-24 21:00:00 UTC'),
            ],
            $interval->getInterval($timestamp, 'Europe/Sofia')
        );
    }

    public function testReturnsNextWeekWhenTimestampIsAtNextLocalMondayStart(): void
    {
        $interval = new WeekInterval();

        // Monday 2026-05-25 00:00 Europe/Sofia
        $timestamp = strtotime('2026-05-24 21:00:00 UTC');

        self::assertSame(
            [
                'start' => strtotime('2026-05-24 21:00:00 UTC'),
                'end' => strtotime('2026-05-31 21:00:00 UTC'),
            ],
            $interval->getInterval($timestamp, 'Europe/Sofia')
        );
    }

    public function testHandlesWeekContainingDstSpringForwardInEuropeSofia(): void
    {
        $interval = new WeekInterval();

        // DST starts in Europe/Sofia on 2026-03-29.
        $timestamp = strtotime('2026-03-29 12:00:00 UTC');

        $result = $interval->getInterval($timestamp, 'Europe/Sofia');

        self::assertSame(
            strtotime('2026-03-22 22:00:00 UTC'),
            $result['start']
        );

        self::assertSame(
            strtotime('2026-03-29 21:00:00 UTC'),
            $result['end']
        );

        self::assertSame(
            (7 * 24 - 1) * 3600,
            $result['end'] - $result['start']
        );
    }

    public function testHandlesWeekContainingDstFallBackInEuropeSofia(): void
    {
        $interval = new WeekInterval();

        // DST ends in Europe/Sofia on 2026-10-25.
        $timestamp = strtotime('2026-10-25 12:00:00 UTC');

        $result = $interval->getInterval($timestamp, 'Europe/Sofia');

        self::assertSame(
            strtotime('2026-10-18 21:00:00 UTC'),
            $result['start']
        );

        self::assertSame(
            strtotime('2026-10-25 22:00:00 UTC'),
            $result['end']
        );

        self::assertSame(
            (7 * 24 + 1) * 3600,
            $result['end'] - $result['start']
        );
    }

    public function testThrowsExceptionWhenIntervalIsZero(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Interval must be greater than zero.');

        WeekInterval::for(0);
    }

    public function testThrowsExceptionWhenIntervalIsNegative(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Interval must be greater than zero.');

        WeekInterval::for(-1);
    }
}