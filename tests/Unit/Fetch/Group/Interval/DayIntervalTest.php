<?php

namespace Davos\Graphy\Tests\Unit\Fetch\Group\Interval;

use Davos\Graphy\Fetch\Group\Interval\DayInterval;
use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use PHPUnit\Framework\TestCase;

final class DayIntervalTest extends TestCase
{
    public function testCreatesOneDayIntervalInUtcByDefault(): void
    {
        $interval = new DayInterval();

        $timestamp = strtotime('2026-05-20 13:45:00 UTC');

        self::assertSame(
            [
                'start' => strtotime('2026-05-20 00:00:00 UTC'),
                'end' => strtotime('2026-05-21 00:00:00 UTC'),
            ],
            $interval->getInterval($timestamp)
        );
    }

    public function testCreatesMultipleDayIntervalInUtcByDefault(): void
    {
        $interval = DayInterval::for(3);

        $timestamp = strtotime('2026-05-20 13:45:00 UTC');

        self::assertSame(
            [
                'start' => strtotime('2026-05-20 00:00:00 UTC'),
                'end' => strtotime('2026-05-23 00:00:00 UTC'),
            ],
            $interval->getInterval($timestamp)
        );
    }

    public function testCreatesOneDayIntervalInEuropeSofiaSummerTime(): void
    {
        $interval = new DayInterval();

        $timestamp = strtotime('2026-05-20 13:45:00 UTC');

        self::assertSame(
            [
                // 2026-05-20 00:00:00 Europe/Sofia = 2026-05-19 21:00:00 UTC
                'start' => strtotime('2026-05-19 21:00:00 UTC'),

                // 2026-05-21 00:00:00 Europe/Sofia = 2026-05-20 21:00:00 UTC
                'end' => strtotime('2026-05-20 21:00:00 UTC'),
            ],
            $interval->getInterval($timestamp, 'Europe/Sofia')
        );
    }

    public function testCreatesOneDayIntervalInEuropeSofiaWinterTime(): void
    {
        $interval = new DayInterval();

        $timestamp = strtotime('2026-01-20 13:45:00 UTC');

        self::assertSame(
            [
                // 2026-01-20 00:00:00 Europe/Sofia = 2026-01-19 22:00:00 UTC
                'start' => strtotime('2026-01-19 22:00:00 UTC'),

                // 2026-01-21 00:00:00 Europe/Sofia = 2026-01-20 22:00:00 UTC
                'end' => strtotime('2026-01-20 22:00:00 UTC'),
            ],
            $interval->getInterval($timestamp, 'Europe/Sofia')
        );
    }

    public function testReturnsIntervalWhenTimestampIsExactlyAtLocalStartOfDay(): void
    {
        $interval = new DayInterval();

        // 2026-05-20 00:00:00 Europe/Sofia
        $timestamp = strtotime('2026-05-19 21:00:00 UTC');

        self::assertSame(
            [
                'start' => strtotime('2026-05-19 21:00:00 UTC'),
                'end' => strtotime('2026-05-20 21:00:00 UTC'),
            ],
            $interval->getInterval($timestamp, 'Europe/Sofia')
        );
    }

    public function testReturnsIntervalWhenTimestampIsInsideLocalDay(): void
    {
        $interval = new DayInterval();

        // 2026-05-20 23:59:59 Europe/Sofia
        $timestamp = strtotime('2026-05-20 20:59:59 UTC');

        self::assertSame(
            [
                'start' => strtotime('2026-05-19 21:00:00 UTC'),
                'end' => strtotime('2026-05-20 21:00:00 UTC'),
            ],
            $interval->getInterval($timestamp, 'Europe/Sofia')
        );
    }

    public function testReturnsNextIntervalWhenTimestampIsAtNextLocalDayBoundary(): void
    {
        $interval = new DayInterval();

        // 2026-05-21 00:00:00 Europe/Sofia
        $timestamp = strtotime('2026-05-20 21:00:00 UTC');

        self::assertSame(
            [
                'start' => strtotime('2026-05-20 21:00:00 UTC'),
                'end' => strtotime('2026-05-21 21:00:00 UTC'),
            ],
            $interval->getInterval($timestamp, 'Europe/Sofia')
        );
    }

    public function testHandlesDstSpringForwardDayInEuropeSofia(): void
    {
        $interval = new DayInterval();

        // DST starts in Europe/Sofia on 2026-03-29.
        $timestamp = strtotime('2026-03-29 12:00:00 UTC');

        $result = $interval->getInterval($timestamp, 'Europe/Sofia');

        self::assertSame(
            strtotime('2026-03-28 22:00:00 UTC'),
            $result['start']
        );

        self::assertSame(
            strtotime('2026-03-29 21:00:00 UTC'),
            $result['end']
        );

        self::assertSame(
            23 * 3600,
            $result['end'] - $result['start']
        );
    }

    public function testHandlesDstFallBackDayInEuropeSofia(): void
    {
        $interval = new DayInterval();

        // DST ends in Europe/Sofia on 2026-10-25.
        $timestamp = strtotime('2026-10-25 12:00:00 UTC');

        $result = $interval->getInterval($timestamp, 'Europe/Sofia');

        self::assertSame(
            strtotime('2026-10-24 21:00:00 UTC'),
            $result['start']
        );

        self::assertSame(
            strtotime('2026-10-25 22:00:00 UTC'),
            $result['end']
        );

        self::assertSame(
            25 * 3600,
            $result['end'] - $result['start']
        );
    }

    public function testThrowsExceptionWhenIntervalIsZero(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Interval must be greater than zero.');

        DayInterval::for(0);
    }

    public function testThrowsExceptionWhenIntervalIsNegative(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Interval must be greater than zero.');

        DayInterval::for(-1);
    }
}
