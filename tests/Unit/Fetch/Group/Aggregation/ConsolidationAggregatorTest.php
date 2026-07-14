<?php

namespace Davos\Graphy\Tests\Unit\Fetch\Group\Aggregation;

use Davos\Graphy\Fetch\Group\Aggregation\ConsolidationAggregator;
use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use PHPUnit\Framework\TestCase;

class ConsolidationAggregatorTest extends TestCase
{
    private ConsolidationAggregator $aggregator;

    protected function setUp(): void
    {
        $this->aggregator = new ConsolidationAggregator();
    }

    public function testAverageReturnsFloat(): void
    {
        self::assertSame(
            30.0,
            $this->aggregator->aggregate([10, 20, 30, 40, 50], 'AVERAGE')
        );
    }

    public function testMinReturnsFloat(): void
    {
        self::assertSame(
            10.0,
            $this->aggregator->aggregate([10, 20, 30, 40, 50], 'MIN')
        );
    }

    public function testMaxReturnsFloat(): void
    {
        self::assertSame(
            50.0,
            $this->aggregator->aggregate([10, 20, 30, 40, 50], 'MAX')
        );
    }

    public function testLastReturnsFloat(): void
    {
        self::assertSame(
            50.0,
            $this->aggregator->aggregate([10, 20, 30, 40, 50], 'LAST')
        );
    }

    public function testAverageWithDecimalResult(): void
    {
        self::assertEqualsWithDelta(
            68.3333333333,
            $this->aggregator->aggregate([90, 84, 74, 64, 54, 44], 'AVERAGE'),
            0.0000001
        );
    }

    public function testCfIsCaseInsensitive(): void
    {
        self::assertSame(
            30.0,
            $this->aggregator->aggregate([10, 20, 30, 40, 50], 'average')
        );
    }

    public function testEmptyValuesReturnsZeroFloat(): void
    {
        self::assertNan($this->aggregator->aggregate([], 'AVERAGE'));
    }

    public function testUnsupportedCfThrowsException(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Unsupported CF: TOTAL');

        $this->aggregator->aggregate([10, 20, 30], 'TOTAL');
    }

    public function testUnsupportedCfThrowsExceptionForEmptyValues(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Unsupported CF: TOTAL');

        $this->aggregator->aggregate([], 'TOTAL');
    }

    public function testUnsupportedCfThrowsExceptionForAllNanValues(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Unsupported CF: TOTAL');

        $this->aggregator->aggregate([NAN, NAN], 'TOTAL');
    }

    public function testNonNumericValueThrowsException(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Value must be an integer or a float. string is given');

        $this->aggregator->aggregate([10, '20', 30], 'AVERAGE');
    }

    public function testNullValueThrowsException(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Value must be an integer or a float. null is given');

        $this->aggregator->aggregate([10, null, 30], 'AVERAGE');
    }

    public function testAverageWithFloatValues(): void
    {
        self::assertSame(
            25.0,
            $this->aggregator->aggregate([10.0, 20.0, 30.0, 40.0], 'AVERAGE')
        );
    }

    public function testMinWithFloatValues(): void
    {
        self::assertSame(
            10.5,
            $this->aggregator->aggregate([20.5, 10.5, 30.5], 'MIN')
        );
    }

    public function testMaxWithFloatValues(): void
    {
        self::assertSame(
            30.5,
            $this->aggregator->aggregate([20.5, 10.5, 30.5], 'MAX')
        );
    }

    public function testLastWithFloatValues(): void
    {
        self::assertSame(
            30.5,
            $this->aggregator->aggregate([20.5, 10.5, 30.5], 'LAST')
        );
    }

    public function testAverageWithMixedIntegerAndFloatValues(): void
    {
        self::assertSame(
            25.125,
            $this->aggregator->aggregate([10, 20.5, 30, 40.0], 'AVERAGE')
        );
    }

    public function testMinWithMixedIntegerAndFloatValues(): void
    {
        self::assertSame(
            10.0,
            $this->aggregator->aggregate([10, 20.5, 30, 40.0], 'MIN')
        );
    }

    public function testMaxWithMixedIntegerAndFloatValues(): void
    {
        self::assertSame(
            40.0,
            $this->aggregator->aggregate([10, 20.5, 30, 40.0], 'MAX')
        );
    }

    public function testLastWithMixedIntegerAndFloatValues(): void
    {
        self::assertSame(
            40.0,
            $this->aggregator->aggregate([10, 20.5, 30, 40.0], 'LAST')
        );
    }

    public function testAverageAlwaysReturnsFloat(): void
    {
        $result = $this->aggregator->aggregate([10, 20, 30], 'AVERAGE');

        self::assertSame(20.0, $result);
    }

    public function testAverageIgnoresNanValues(): void
    {
        self::assertSame(
            20.0,
            $this->aggregator->aggregate([10, NAN, 20, NAN, 30], 'AVERAGE')
        );
    }

    public function testMinIgnoresNanValues(): void
    {
        self::assertSame(
            10.0,
            $this->aggregator->aggregate([NAN, 20, 10, NAN, 30], 'MIN')
        );
    }

    public function testMaxIgnoresNanValues(): void
    {
        self::assertSame(
            30.0,
            $this->aggregator->aggregate([NAN, 20, 10, NAN, 30], 'MAX')
        );
    }

    public function testLastIgnoresNanValuesAndReturnsLastKnownValue(): void
    {
        self::assertSame(
            30.0,
            $this->aggregator->aggregate([10, 20, NAN, 30, NAN], 'LAST')
        );
    }

    public function testAverageReturnsNanWhenAllValuesAreNan(): void
    {
        $result = $this->aggregator->aggregate([NAN, NAN], 'AVERAGE');

        self::assertNan($result);
    }

    public function testMinReturnsNanWhenAllValuesAreNan(): void
    {
        $result = $this->aggregator->aggregate([NAN, NAN], 'MIN');

        self::assertNan($result);
    }

    public function testMaxReturnsNanWhenAllValuesAreNan(): void
    {
        $result = $this->aggregator->aggregate([NAN, NAN], 'MAX');

        self::assertNan($result);
    }

    public function testLastReturnsNanWhenAllValuesAreNan(): void
    {
        $result = $this->aggregator->aggregate([NAN, NAN], 'LAST');

        self::assertNan($result);
    }
}
