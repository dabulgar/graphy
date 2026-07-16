<?php

namespace Davos\Graphy\Tests\Unit\Fetch\Group;

use Davos\Graphy\Fetch\Group\Interval\DayInterval;
use Davos\Graphy\Fetch\Group\Interval\SecondInterval;
use Davos\Graphy\Fetch\Group\TimestampWatermark;
use Davos\Graphy\Fetch\Group\WatermarkLevel;
use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use PHPUnit\Framework\TestCase;

final class TimestampWatermarkTest extends TestCase
{
    public function testStartsFirstInterval(): void
    {
        $watermark = new TimestampWatermark(SecondInterval::for(10));

        $level = $watermark->level(123);

        self::assertSame(WatermarkLevel::Started, $level);
        self::assertSame(120, $watermark->start);
        self::assertSame(130, $watermark->end);
    }

    public function testReturnsInsideWhenTimestampIsBeforeCurrentEnd(): void
    {
        $watermark = new TimestampWatermark(SecondInterval::for(10));

        self::assertSame(WatermarkLevel::Started, $watermark->level(123));
        self::assertSame(WatermarkLevel::Inside, $watermark->level(124));
        self::assertSame(WatermarkLevel::Inside, $watermark->level(129));

        self::assertSame(120, $watermark->start);
        self::assertSame(130, $watermark->end);
    }

    public function testReturnsHitBoundaryWhenTimestampEqualsCurrentEnd(): void
    {
        $watermark = new TimestampWatermark(SecondInterval::for(10));

        self::assertSame(WatermarkLevel::Started, $watermark->level(123));

        $level = $watermark->level(130);

        self::assertSame(WatermarkLevel::HitBoundary, $level);
        self::assertSame(120, $watermark->start);
        self::assertSame(130, $watermark->end);
    }

    public function testStartsNextIntervalAfterPreviousBoundaryWasHit(): void
    {
        $watermark = new TimestampWatermark(SecondInterval::for(10));

        self::assertSame(WatermarkLevel::Started, $watermark->level(123));
        self::assertSame(WatermarkLevel::HitBoundary, $watermark->level(130));

        $level = $watermark->level(131);

        self::assertSame(WatermarkLevel::Started, $level);
        self::assertSame(130, $watermark->start);
        self::assertSame(140, $watermark->end);
    }

    public function testThrowsWhenTimestampPassesEndWithoutHittingBoundary(): void
    {
        $watermark = new TimestampWatermark(SecondInterval::for(10));

        self::assertSame(WatermarkLevel::Started, $watermark->level(123));

        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage(
            'Cannot determine next interval because previous interval end was not reached exactly.'
        );

        $watermark->level(131);
    }

    public function testFirstTimestampExactlyOnBucketStartClosesPreviousBucket(): void
    {
        $watermark = new TimestampWatermark(SecondInterval::for(10));

        $level = $watermark->level(120);

        self::assertSame(WatermarkLevel::HitBoundary, $level);

        // Because RRD timestamps are interval-end timestamps:
        // timestamp 120 belongs to bucket 110 -> 120, not 120 -> 130.
        self::assertSame(110, $watermark->start);
        self::assertSame(120, $watermark->end);
    }

    public function testFirstTimestampExactlyOnDayStartClosesPreviousDayBucket(): void
    {
        $watermark = new TimestampWatermark(DayInterval::for(1), 'UTC');

        $timestamp = strtotime('2026-05-21 00:00:00 UTC');

        $level = $watermark->level($timestamp);

        self::assertSame(WatermarkLevel::HitBoundary, $level);

        self::assertSame(
            strtotime('2026-05-20 00:00:00 UTC'),
            $watermark->start
        );

        self::assertSame(
            strtotime('2026-05-21 00:00:00 UTC'),
            $watermark->end
        );
    }

    public function testFirstTimestampExactlyOnSofiaDayStartClosesPreviousSofiaDayBucket(): void
    {
        $watermark = new TimestampWatermark(DayInterval::for(1), 'Europe/Sofia');

        // 2026-05-21 00:00:00 Europe/Sofia
        $timestamp = strtotime('2026-05-20 21:00:00 UTC');

        $level = $watermark->level($timestamp);

        self::assertSame(WatermarkLevel::HitBoundary, $level);

        // Previous Sofia day:
        // 2026-05-20 00:00:00 Europe/Sofia = 2026-05-19 21:00:00 UTC
        self::assertSame(
            strtotime('2026-05-19 21:00:00 UTC'),
            $watermark->start
        );

        // 2026-05-21 00:00:00 Europe/Sofia = 2026-05-20 21:00:00 UTC
        self::assertSame(
            strtotime('2026-05-20 21:00:00 UTC'),
            $watermark->end
        );
    }

    public function testSofiaDayBoundaryWithThirtyMinuteStepLikeRrdFlow(): void
    {
        $watermark = new TimestampWatermark(DayInterval::for(1), 'Europe/Sofia');

        // Sofia day 2026-05-20:
        // start UTC = 2026-05-19 21:00
        // end UTC   = 2026-05-20 21:00
        //
        // These timestamps simulate 30m RRD interval-end timestamps.
        $timestamps = [
            strtotime('2026-05-19 21:30:00 UTC'),
            strtotime('2026-05-19 22:00:00 UTC'),
            strtotime('2026-05-20 20:30:00 UTC'),
            strtotime('2026-05-20 21:00:00 UTC'),
        ];

        self::assertSame(WatermarkLevel::Started, $watermark->level($timestamps[0]));
        self::assertSame(strtotime('2026-05-19 21:00:00 UTC'), $watermark->start);
        self::assertSame(strtotime('2026-05-20 21:00:00 UTC'), $watermark->end);

        self::assertSame(WatermarkLevel::Inside, $watermark->level($timestamps[1]));
        self::assertSame(WatermarkLevel::Inside, $watermark->level($timestamps[2]));

        self::assertSame(WatermarkLevel::HitBoundary, $watermark->level($timestamps[3]));

        self::assertSame(strtotime('2026-05-19 21:00:00 UTC'), $watermark->start);
        self::assertSame(strtotime('2026-05-20 21:00:00 UTC'), $watermark->end);
    }

    public function testUtcDayBoundaryTimestampsCloseConsecutiveDayBuckets(): void
    {
        $watermark = new TimestampWatermark(DayInterval::for(1));

        $timestamps = [
            strtotime('2026-05-19 00:00:00 UTC'),
            strtotime('2026-05-20 00:00:00 UTC'),
            strtotime('2026-05-21 00:00:00 UTC'),
            strtotime('2026-05-22 00:00:00 UTC'),
            strtotime('2026-05-23 00:00:00 UTC'),
        ];

        self::assertSame(WatermarkLevel::HitBoundary, $watermark->level($timestamps[0]));
        self::assertSame(WatermarkLevel::HitBoundary, $watermark->level($timestamps[1]));
        self::assertSame(WatermarkLevel::HitBoundary, $watermark->level($timestamps[2]));
        self::assertSame(WatermarkLevel::HitBoundary, $watermark->level($timestamps[3]));
        self::assertSame(WatermarkLevel::HitBoundary, $watermark->level($timestamps[4]));
    }

    public function testStartsNextSofiaDayAfterBoundaryWasHit(): void
    {
        $watermark = new TimestampWatermark(DayInterval::for(1), 'Europe/Sofia');

        self::assertSame(
            WatermarkLevel::Started,
            $watermark->level(strtotime('2026-05-20 20:30:00 UTC'))
        );

        self::assertSame(
            WatermarkLevel::HitBoundary,
            $watermark->level(strtotime('2026-05-20 21:00:00 UTC'))
        );

        self::assertSame(
            WatermarkLevel::Started,
            $watermark->level(strtotime('2026-05-20 21:30:00 UTC'))
        );

        self::assertSame(
            strtotime('2026-05-20 21:00:00 UTC'),
            $watermark->start
        );

        self::assertSame(
            strtotime('2026-05-21 21:00:00 UTC'),
            $watermark->end
        );
    }

    public function testThrowsForSofiaDayWhenTwelveHourUtcGridCannotHitBoundary(): void
    {
        $watermark = new TimestampWatermark(DayInterval::for(1), 'Europe/Sofia');

        // UTC-aligned 12h RRD timestamps.
        // Sofia day boundary in summer is 21:00 UTC, so this grid will skip it.
        self::assertSame(
            WatermarkLevel::Started,
            $watermark->level(strtotime('2026-05-20 12:00:00 UTC'))
        );

        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage(
            'Cannot determine next interval because previous interval end was not reached exactly.'
        );

        // Current bucket ends at 2026-05-20 21:00 UTC.
        // This timestamp jumps past it without hitting it exactly.
        $watermark->level(strtotime('2026-05-21 00:00:00 UTC'));
    }
}
