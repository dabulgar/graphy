<?php

namespace Davos\Graphy\Tests\Unit\Fetch\Group\Interval;

use Davos\Graphy\Fetch\Group\Interval\HourInterval;
use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use PHPUnit\Framework\TestCase;

final class HourIntervalTest extends TestCase
{
    public function testCreatesIntervalWithDefaultOneHour(): void
    {
        $interval = new HourInterval();

        self::assertSame(
            ['start' => 0, 'end' => 3600],
            $interval->getInterval(123)
        );
    }

    public function testCreatesIntervalUsingFactoryMethod(): void
    {
        $interval = HourInterval::for(12);

        self::assertSame(
            ['start' => 0, 'end' => 43200],
            $interval->getInterval(420)
        );
    }

    public function testReturnsIntervalForTimestampExactlyOnBoundary(): void
    {
        $interval = HourInterval::for(12);

        self::assertSame(
            ['start' => 43200, 'end' => 86400],
            $interval->getInterval(43200)
        );
    }

    public function testReturnsIntervalForTimestampInsideBoundary(): void
    {
        $interval = HourInterval::for(12);

        self::assertSame(
            ['start' => 43200, 'end' => 86400],
            $interval->getInterval(86399)
        );
    }

    public function testReturnsNextIntervalWhenTimestampIsOnNextBoundary(): void
    {
        $interval = HourInterval::for(12);

        self::assertSame(
            ['start' => 86400, 'end' => 129600],
            $interval->getInterval(86400)
        );
    }

    public function testIgnoresTimezoneArgument(): void
    {
        $interval = HourInterval::for(12);

        self::assertSame(
            ['start' => 0, 'end' => 43200],
            $interval->getInterval(420, 'Europe/Sofia')
        );
    }

    public function testWorksWithRealUnixTimestampForOneHour(): void
    {
        $interval = HourInterval::for(1);

        self::assertSame(
            [
                'start' => 1710000000,
                'end' => 1710003600,
            ],
            $interval->getInterval(1710000457)
        );
    }

    public function testWorksWithRealUnixTimestampForTwelveHours(): void
    {
        $interval = HourInterval::for(12);

        self::assertSame(
            [
                'start' => 1709985600,
                'end' => 1710028800,
            ],
            $interval->getInterval(1710000457)
        );
    }

    public function testThrowsExceptionWhenIntervalIsZero(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Interval must be greater than zero.');

        HourInterval::for(0);
    }

    public function testThrowsExceptionWhenIntervalIsNegative(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Interval must be greater than zero.');

        HourInterval::for(-12);
    }
}