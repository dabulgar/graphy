<?php

namespace Davos\Graphy\Tests\Unit\Fetch\Group\Interval;

use Davos\Graphy\Fetch\Group\Interval\MinuteInterval;
use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use PHPUnit\Framework\TestCase;

final class MinuteIntervalTest extends TestCase
{
    public function testCreatesIntervalWithDefaultOneMinute(): void
    {
        $interval = new MinuteInterval();

        self::assertSame(
            ['start' => 120, 'end' => 180],
            $interval->getInterval(123)
        );
    }

    public function testCreatesIntervalUsingFactoryMethod(): void
    {
        $interval = MinuteInterval::for(5);

        self::assertSame(
            ['start' => 300, 'end' => 600],
            $interval->getInterval(420)
        );
    }

    public function testReturnsIntervalForTimestampExactlyOnBoundary(): void
    {
        $interval = MinuteInterval::for(5);

        self::assertSame(
            ['start' => 300, 'end' => 600],
            $interval->getInterval(300)
        );
    }

    public function testReturnsIntervalForTimestampInsideBoundary(): void
    {
        $interval = MinuteInterval::for(5);

        self::assertSame(
            ['start' => 300, 'end' => 600],
            $interval->getInterval(599)
        );
    }

    public function testReturnsNextIntervalWhenTimestampIsOnNextBoundary(): void
    {
        $interval = MinuteInterval::for(5);

        self::assertSame(
            ['start' => 600, 'end' => 900],
            $interval->getInterval(600)
        );
    }

    public function testIgnoresTimezoneArgument(): void
    {
        $interval = MinuteInterval::for(5);

        self::assertSame(
            ['start' => 300, 'end' => 600],
            $interval->getInterval(420, 'Europe/Sofia')
        );
    }

    public function testWorksWithRealUnixTimestampForFiveMinutes(): void
    {
        $interval = MinuteInterval::for(5);

        self::assertSame(
            [
                'start' => 1710000300,
                'end' => 1710000600,
            ],
            $interval->getInterval(1710000457)
        );
    }

    public function testWorksWithRealUnixTimestampForThirtyMinutes(): void
    {
        $interval = MinuteInterval::for(30);

        self::assertSame(
            [
                'start' => 1710000000,
                'end' => 1710001800,
            ],
            $interval->getInterval(1710000457)
        );
    }

    public function testThrowsExceptionWhenIntervalIsZero(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Interval must be greater than zero.');

        MinuteInterval::for(0);
    }

    public function testThrowsExceptionWhenIntervalIsNegative(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Interval must be greater than zero.');

        MinuteInterval::for(-5);
    }
}
