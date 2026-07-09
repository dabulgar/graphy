<?php

namespace Davos\Graphy\Tests\Unit\Fetch\Group\Interval;

use Davos\Graphy\Fetch\Group\Interval\MonthInterval;
use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use PHPUnit\Framework\TestCase;

final class MonthIntervalTest extends TestCase
{
    public function testCreatesOneMonthIntervalInUtcByDefault(): void
    {
        $interval = new MonthInterval();

        $timestamp = strtotime('2026-05-20 13:45:00 UTC');

        self::assertSame(
            [
                'start' => strtotime('2026-05-01 00:00:00 UTC'),
                'end' => strtotime('2026-06-01 00:00:00 UTC'),
            ],
            $interval->getInterval($timestamp)
        );
    }

    public function testCreatesMultipleMonthIntervalInUtcByDefault(): void
    {
        $interval = MonthInterval::for(3);

        $timestamp = strtotime('2026-05-20 13:45:00 UTC');

        self::assertSame(
            [
                'start' => strtotime('2026-05-01 00:00:00 UTC'),
                'end' => strtotime('2026-08-01 00:00:00 UTC'),
            ],
            $interval->getInterval($timestamp)
        );
    }

    public function testCreatesOneMonthIntervalInEuropeSofiaSummerTime(): void
    {
        $interval = new MonthInterval();

        $timestamp = strtotime('2026-05-20 13:45:00 UTC');

        self::assertSame(
            [
                // 2026-05-01 00:00 Europe/Sofia = 2026-04-30 21:00 UTC
                'start' => strtotime('2026-04-30 21:00:00 UTC'),

                // 2026-06-01 00:00 Europe/Sofia = 2026-05-31 21:00 UTC
                'end' => strtotime('2026-05-31 21:00:00 UTC'),
            ],
            $interval->getInterval($timestamp, 'Europe/Sofia')
        );
    }

    public function testCreatesOneMonthIntervalInEuropeSofiaWinterTime(): void
    {
        $interval = new MonthInterval();

        $timestamp = strtotime('2026-01-20 13:45:00 UTC');

        self::assertSame(
            [
                // 2026-01-01 00:00 Europe/Sofia = 2025-12-31 22:00 UTC
                'start' => strtotime('2025-12-31 22:00:00 UTC'),

                // 2026-02-01 00:00 Europe/Sofia = 2026-01-31 22:00 UTC
                'end' => strtotime('2026-01-31 22:00:00 UTC'),
            ],
            $interval->getInterval($timestamp, 'Europe/Sofia')
        );
    }

    public function testReturnsSameMonthWhenTimestampIsExactlyAtLocalMonthStart(): void
    {
        $interval = new MonthInterval();

        // 2026-05-01 00:00 Europe/Sofia
        $timestamp = strtotime('2026-04-30 21:00:00 UTC');

        self::assertSame(
            [
                'start' => strtotime('2026-04-30 21:00:00 UTC'),
                'end' => strtotime('2026-05-31 21:00:00 UTC'),
            ],
            $interval->getInterval($timestamp, 'Europe/Sofia')
        );
    }

    public function testReturnsNextMonthWhenTimestampIsAtNextLocalMonthStart(): void
    {
        $interval = new MonthInterval();

        // 2026-06-01 00:00 Europe/Sofia
        $timestamp = strtotime('2026-05-31 21:00:00 UTC');

        self::assertSame(
            [
                'start' => strtotime('2026-05-31 21:00:00 UTC'),
                'end' => strtotime('2026-06-30 21:00:00 UTC'),
            ],
            $interval->getInterval($timestamp, 'Europe/Sofia')
        );
    }

    public function testHandlesFebruaryInNonLeapYear(): void
    {
        $interval = new MonthInterval();

        $timestamp = strtotime('2026-02-15 12:00:00 UTC');

        $result = $interval->getInterval($timestamp);

        self::assertSame(
            [
                'start' => strtotime('2026-02-01 00:00:00 UTC'),
                'end' => strtotime('2026-03-01 00:00:00 UTC'),
            ],
            $result
        );

        self::assertSame(28 * 24 * 3600, $result['end'] - $result['start']);
    }

    public function testHandlesFebruaryInLeapYear(): void
    {
        $interval = new MonthInterval();

        $timestamp = strtotime('2024-02-15 12:00:00 UTC');

        $result = $interval->getInterval($timestamp);

        self::assertSame(
            [
                'start' => strtotime('2024-02-01 00:00:00 UTC'),
                'end' => strtotime('2024-03-01 00:00:00 UTC'),
            ],
            $result
        );

        self::assertSame(29 * 24 * 3600, $result['end'] - $result['start']);
    }

    public function testHandlesMonthContainingDstSpringForwardInEuropeSofia(): void
    {
        $interval = new MonthInterval();

        $timestamp = strtotime('2026-03-15 12:00:00 UTC');

        $result = $interval->getInterval($timestamp, 'Europe/Sofia');

        self::assertSame(
            strtotime('2026-02-28 22:00:00 UTC'), // 2026-03-01 00:00 EET
            $result['start']
        );

        self::assertSame(
            strtotime('2026-03-31 21:00:00 UTC'), // 2026-04-01 00:00 EEST
            $result['end']
        );

        self::assertSame(
            (31 * 24 - 1) * 3600,
            $result['end'] - $result['start']
        );
    }

    public function testHandlesMonthContainingDstFallBackInEuropeSofia(): void
    {
        $interval = new MonthInterval();

        $timestamp = strtotime('2026-10-15 12:00:00 UTC');

        $result = $interval->getInterval($timestamp, 'Europe/Sofia');

        self::assertSame(
            strtotime('2026-09-30 21:00:00 UTC'), // 2026-10-01 00:00 EEST
            $result['start']
        );

        self::assertSame(
            strtotime('2026-10-31 22:00:00 UTC'), // 2026-11-01 00:00 EET
            $result['end']
        );

        self::assertSame(
            (31 * 24 + 1) * 3600,
            $result['end'] - $result['start']
        );
    }

    public function testThrowsExceptionWhenIntervalIsZero(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Interval must be greater than zero.');

        MonthInterval::for(0);
    }

    public function testThrowsExceptionWhenIntervalIsNegative(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Interval must be greater than zero.');

        MonthInterval::for(-1);
    }
}