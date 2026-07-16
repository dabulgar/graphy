<?php

namespace Davos\Graphy\Tests\Unit\ValueObjects;

use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use Davos\Graphy\ValueObjects\Exceptions\TimeReferenceException;
use Davos\Graphy\ValueObjects\TimeReference;
use PHPUnit\Framework\TestCase;

class TimeReferenceTest extends TestCase
{
    private array $resolved;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolved = [
            'now' => 1_700_000_000,
            'end' => 1_699_900_000,
            'start' => 1_699_800_000,
        ];
    }

    public function testCreatesFromIntegerTimestamp(): void
    {
        $reference = new TimeReference(1700000100, $this->resolved);

        $this->assertSame(1700000100, $reference->getTimestamp());
        $this->assertSame(1700000100, $reference->getOriginal());
    }

    public function testCreatesFromNumericStringTimestamp(): void
    {
        $reference = new TimeReference('1700000200', $this->resolved);

        $this->assertSame(1700000200, $reference->getTimestamp());
        $this->assertSame('1700000200', $reference->getOriginal());
    }

    public function testCreatesFromRelativeNowMinusOneHour(): void
    {
        $reference = new TimeReference('now-1h', $this->resolved);

        $this->assertSame($this->resolved['now'] - 3600, $reference->getTimestamp());
        $this->assertSame('now-1h', $reference->getOriginal());
    }

    public function testCreatesFromNowAnchorOnly(): void
    {
        $reference = new TimeReference('now', $this->resolved);

        $this->assertSame($this->resolved['now'], $reference->getTimestamp());
    }

    public function testCreatesFromEndMinusOneYear(): void
    {
        $reference = new TimeReference('end-1y', $this->resolved);

        // one year seconds according to rrdtool
        $this->assertSame($this->resolved['end'] - 31622400, $reference->getTimestamp());
    }

    public function testCreatesFromStartMinusTenHours(): void
    {
        $reference = new TimeReference('start-10h', $this->resolved);

        $this->assertSame($this->resolved['start'] - (10 * 3600), $reference->getTimestamp());
    }

    public function testThrowsExceptionWhenTimeIsTooShort(): void
    {
        $time = '+';

        $this->expectException(TimeReferenceException::class);
        $this->expectExceptionMessage(TimeReferenceException::timeTooShort($time)->getMessage());

        new TimeReference($time, $this->resolved);
    }

    public function testThrowsExceptionWhenRelativeExpressionEndsWithOperator(): void
    {
        $time = 'end-';

        $this->expectException(TimeReferenceException::class);
        $this->expectExceptionMessage(TimeReferenceException::invalidTimeReference($time)->getMessage());

        new TimeReference($time, $this->resolved);
    }

    public function testThrowsExceptionWhenRelativeExpressionEndsWithOperatorAfterNowAnchor(): void
    {
        $time = 'now+';

        $this->expectException(TimeReferenceException::class);
        $this->expectExceptionMessage(TimeReferenceException::invalidTimeReference($time)->getMessage());

        new TimeReference($time, $this->resolved);
    }

    public function testThrowsExceptionWhenRelativeExpressionHasDurationWithoutOperator(): void
    {
        $time = 'now1y';

        $this->expectException(TimeReferenceException::class);
        $this->expectExceptionMessage(TimeReferenceException::invalidTimeReference($time)->getMessage());

        new TimeReference($time, $this->resolved);
    }

    public function testThrowsExceptionWhenExpressionContainsUnsupportedCharacter(): void
    {
        $time = 'now&1y';

        $this->expectException(TimeReferenceException::class);
        $this->expectExceptionMessage(TimeReferenceException::invalidTimeReference($time)->getMessage());

        new TimeReference($time, $this->resolved);
    }

    public function testThrowsExceptionWhenExpressionContainsUnknownAnchor(): void
    {
        $time = 'star-1y';

        $this->expectException(TimeReferenceException::class);
        $this->expectExceptionMessage(TimeReferenceException::invalidTimeReference($time)->getMessage());

        new TimeReference($time, $this->resolved);
    }

    public function testThrowsExceptionWhenDurationUsesUnsupportedUppercaseUnit(): void
    {
        $time = 'start-1Y';

        $this->expectException(TimeReferenceException::class);
        $this->expectExceptionMessage(TimeReferenceException::invalidTimeReference($time)->getMessage());

        new TimeReference($time, $this->resolved);
    }

    public function testThrowsExceptionWhenStartAnchorIsUsedButMissingFromResolvedArray(): void
    {
        $resolved = [
            'now' => 1_700_000_000,
            'end' => 1_699_900_000,
        ];

        $this->expectException(TimeReferenceException::class);
        $this->expectExceptionMessage(
            TimeReferenceException::missingAnchorInResolvedArray('start')->getMessage()
        );

        new TimeReference('start-10h', $resolved);
    }

    public function testFallsBackToDateTimeImmutableForDateReference(): void
    {
        $reference = new TimeReference('2024-01-15 10:30:00', $this->resolved);

        $expected = (new \DateTimeImmutable('2024-01-15 10:30:00'))->getTimestamp();

        $this->assertSame($expected, $reference->getTimestamp());
    }

    // NORMALIZER

    public function testNormalizesDurationForStartUsingEndAsDefaultAnchor(): void
    {
        $this->assertSame('end-3600s', TimeReference::normalize('1h', 'end'));
    }

    public function testNormalizesDurationForEndUsingNowAsDefaultAnchor(): void
    {
        $this->assertSame('now-3600s', TimeReference::normalize('1h', 'now'));
    }

    public function testKeepsExplicitAnchor(): void
    {
        $this->assertSame('start+3600s', TimeReference::normalize('start+1h', 'now'));
    }

    public function testReturnsIntegerTimestampUnchanged(): void
    {
        $this->assertSame(1700000000, TimeReference::normalize(1700000000, 'now'));
    }

    public function testReturnNormalizedReferenceWithMissingOperator(): void
    {
        $this->assertSame('now-31622400s', TimeReference::normalize('now1y', 'now'));
    }

    public function testReturnNormalizedReferenceWithOnlyNumber(): void
    {
        $this->assertSame('now-1s', TimeReference::normalize('1', 'now'));
    }

    public function testReturnNormalizedReferenceFromReferenceWithoutDuration(): void
    {
        $this->assertSame('end-10s', TimeReference::normalize('-10', 'end'));
        $this->assertSame('now+10s', TimeReference::normalize('+10', 'now'));
        $this->assertSame('now-10s', TimeReference::normalize('now-10', 'end'));
    }

    public function testReturnsAnchorWithoutDuration(): void
    {
        $this->assertSame('now', TimeReference::normalize('now', 'end'));
        $this->assertSame('start', TimeReference::normalize('start', 'now'));
        $this->assertSame('end', TimeReference::normalize('end', 'now'));
    }

    public function testReturnsInvalidExpressionUnchangedForDateTimeParsingLater(): void
    {
        $this->assertSame('yesterday', TimeReference::normalize('yesterday', 'now'));
        $this->assertSame('next monday', TimeReference::normalize('next monday', 'now'));
    }

    public function testThrowsExceptionWhenReferenceIsEmpty(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage((TimeReferenceException::invalidTimeReference('empty string'))->getMessage());

        TimeReference::normalize('', 'now');
    }

    public function testNormalizesExplicitAnchorWithDurationAndNoOperator(): void
    {
        $this->assertSame('end-60s', TimeReference::normalize('end1m', 'now'));
        $this->assertSame('start-60s', TimeReference::normalize('start1m', 'now'));
    }

    public function testThrowsExceptionWhenMissingDuration(): void
    {
        $this->expectException(CommandDefinitionException::class);

        TimeReference::normalize('now-', 'now');
    }


    // FULL
    public function testNormalizedReferenceCanBeParsed(): void
    {
        $normalized = TimeReference::normalize('1h', 'now');

        $reference = new TimeReference($normalized, $this->resolved);

        $this->assertSame($this->resolved['now'] - 3600, $reference->getTimestamp());
    }
}
