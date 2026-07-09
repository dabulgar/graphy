<?php

namespace Davos\Graphy\Tests\Unit\Fetch\Group\Interval;

use Davos\Graphy\Fetch\Group\Interval\SecondInterval;
use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use PHPUnit\Framework\TestCase;

final class SecondIntervalTest extends TestCase
{
    public function testCreatesIntervalWithDefaultOneSecond(): void
    {
        $interval = new SecondInterval();

        self::assertSame(
            ['start' => 10, 'end' => 11],
            $interval->getInterval(10)
        );
    }

    public function testCreatesIntervalUsingFactoryMethod(): void
    {
        $interval = SecondInterval::for(10);

        self::assertSame(
            ['start' => 120, 'end' => 130],
            $interval->getInterval(123)
        );
    }

    public function testReturnsIntervalForTimestampExactlyOnBoundary(): void
    {
        $interval = SecondInterval::for(10);

        self::assertSame(
            ['start' => 120, 'end' => 130],
            $interval->getInterval(120)
        );
    }

    public function testReturnsIntervalForTimestampInsideBoundary(): void
    {
        $interval = SecondInterval::for(10);

        self::assertSame(
            ['start' => 120, 'end' => 130],
            $interval->getInterval(129)
        );
    }

    public function testReturnsNextIntervalWhenTimestampIsOnNextBoundary(): void
    {
        $interval = SecondInterval::for(10);

        self::assertSame(
            ['start' => 130, 'end' => 140],
            $interval->getInterval(130)
        );
    }

    public function testIgnoresTimezoneArgument(): void
    {
        $interval = SecondInterval::for(10);

        self::assertSame(
            ['start' => 120, 'end' => 130],
            $interval->getInterval(123, 'Europe/Sofia')
        );
    }

    public function testThrowsExceptionWhenIntervalIsZero(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Interval must be greater than zero.');

        SecondInterval::for(0);
    }

    public function testThrowsExceptionWhenIntervalIsNegative(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Interval must be greater than zero.');

        SecondInterval::for(-10);
    }

    public function testWorksWithRealUnixTimestamp(): void
    {
        $interval = SecondInterval::for(10);

        self::assertSame(
            [
                'start' => 1710000300,
                'end' => 1710000310,
            ],
            $interval->getInterval(1710000307)
        );
    }
}