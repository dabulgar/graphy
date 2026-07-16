<?php

namespace Davos\Graphy\Tests\Unit\Fetch;

use Davos\Graphy\Fetch\TimeRangeChunker;
use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use Davos\Graphy\ValueObjects\Duration;
use Davos\Graphy\ValueObjects\Exceptions\TimeReferenceException;
use Davos\Graphy\ValueObjects\RoundRobinArchive;
use PHPUnit\Framework\TestCase;

class TimeRangeChunkerTest extends TestCase
{
    public function testReturnsSingleChunkWhenRangeFitsChunkSize(): void
    {
        $now = 1778248927;
        $step = new Duration(1);

        $chunker = new TimeRangeChunker(
            $now - 20,
            $now -10,
            60,
            $step,
            $this->archive("RRA:MAX:0.5:1:40", $now, $step),
            $now,
        );


        $chunks = iterator_to_array($chunker->chunks(), false);

        $this->assertSame([
            ['start' => $now - 20, 'end' => $now - 10]
        ], $chunks);
    }

    public function testChunkerClamps(): void
    {
        $now = 1778248927;
        $step = new Duration(1);

        $chunker = new TimeRangeChunker(
            $now - 48,
            $now,
            60,
            $step,
            $this->archive("RRA:MAX:0.5:1:40", $now, $step),
            $now,
        );


        $chunks = iterator_to_array($chunker->chunks(), false);

        $this->assertSame([
            ['start' => $now - 38, 'end' => $now - 1]
        ], $chunks);
    }

    public function testAlignsStartAndEndToArchiveResolution(): void
    {
        $now = 1778248927;
        $step = new Duration(10);

        $chunker = new TimeRangeChunker(
            $now - 2000,
            $now - 200,
            60,
            $step, // 100
            $this->archive("RRA:MAX:0.5:10:40", $now, $step),
            $now,
        );


        $chunks = iterator_to_array($chunker->chunks(), false);

        $this->assertSame([
            ['start' => $now - ((100 * 20) + 27), 'end' => $now - ((100 * 2) + 27)]
        ], $chunks);
    }

    public function testAlignsAndClampsRangeToAvailableArchiveWindow(): void
    {
        $now = 1778248927;
        $step = new Duration(10);

        $chunker = new TimeRangeChunker(
            $now - 4000,
            $now,
            60,
            $step, // 100
            $this->archive("RRA:MAX:0.5:10:40", $now, $step),
            $now,
        );


        $chunks = iterator_to_array($chunker->chunks(), false);

        $this->assertSame([
            ['start' => $now - ((100 * 38) + 27), 'end' => $now - ((100 * 1) + 27)]
        ], $chunks);
        $this->assertSame($chunker->rows, 37);
    }

    public function testSplitsRangeIntoMultipleChunks(): void
    {
        $now = 1778248927;
        $step = new Duration(1);

        $chunker = new TimeRangeChunker(
            $now - 200,
            $now -10,
            60,
            $step,
            $this->archive("RRA:MAX:0.5:1:400", $now, $step),
            $now,
        );

        $chunks = iterator_to_array($chunker->chunks(), false);

        $this->assertSame([
            ['start' => $now - 200, 'end' => $now - 140],
            ['start' => $now - 140, 'end' => $now - 80],
            ['start' => $now - 80, 'end' => $now - 20],
            ['start' => $now - 20, 'end' => $now - 10],
        ], $chunks);
    }

    public function testSplitsClampedRangeIntoMultipleChunks(): void
    {
        $now = 1778248927;
        $step = new Duration(1);

        $chunker = new TimeRangeChunker(
            $now - 400,
            $now,
            60,
            $step,
            $this->archive("RRA:MAX:0.5:1:400", $now, $step),
            $now,
        );

        $chunks = iterator_to_array($chunker->chunks(), false);

        $this->assertSame([
            ['start' => $now - 398, 'end' => $now - 338],
            ['start' => $now - 338, 'end' => $now - 278],
            ['start' => $now - 278, 'end' => $now - 218],
            ['start' => $now - 218, 'end' => $now - 158],
            ['start' => $now - 158, 'end' => $now - 98],
            ['start' => $now - 98, 'end' => $now - 38],
            ['start' => $now - 38, 'end' => $now - 1],
        ], $chunks);
    }

    public function testSplitsAlignedRangeIntoMultipleChunks(): void
    {
        $now = 1778248927;
        $step = new Duration(10);

        $chunker = new TimeRangeChunker(
            $now - 20027,
            $now -10,
            60,
            $step,
            $this->archive("RRA:MAX:0.5:10:40000", $now, $step),
            $now,
        );

        $chunks = iterator_to_array($chunker->chunks(), false);

        $this->assertSame([
            ['start' => $now - 20027, 'end' => $now - 14027],
            ['start' => $now - 14027, 'end' => $now - 8027],
            ['start' => $now - 8027, 'end' => $now - 2027],
            ['start' => $now - 2027, 'end' => $now - 127],
        ], $chunks);
    }

    public function testSplitsAlignedAndClampedRangeIntoMultipleChunks(): void
    {
        $now = 1778248927;
        $step = new Duration(10);

        $chunker = new TimeRangeChunker(
            $now - 50000,
            $now,
            60,
            $step,
            $this->archive("RRA:MAX:0.5:10:400", $now, $step),
            $now,
        );

        $chunks = iterator_to_array($chunker->chunks(), false);

        $this->assertSame([
            ['start' => $now - 39827, 'end' => $now - 33827],
            ['start' => $now - 33827, 'end' => $now - 27827],
            ['start' => $now - 27827, 'end' => $now - 21827],
            ['start' => $now - 21827, 'end' => $now - 15827],
            ['start' => $now - 15827, 'end' => $now - 9827],
            ['start' => $now - 9827, 'end' => $now - 3827],
            ['start' => $now - 3827, 'end' => $now - 127],
        ], $chunks);
    }

    public function testStartCanReferenceEnd(): void
    {
        $now = 1778248927;
        $step = new Duration(1);

        $chunker = new TimeRangeChunker(
            'end-100s',
            $now - 10,
            60,
            $step,
            $this->archive("RRA:MAX:0.5:1:400", $now, $step),
            $now,
        );

        $chunks = iterator_to_array($chunker->chunks(), false);

        $this->assertSame([
            ['start' => $now - 110, 'end' => $now - 50],
            ['start' => $now - 50, 'end' => $now - 10],
        ], $chunks);
    }

    public function testEndCanReferenceStart(): void
    {
        $now = 1778248927;
        $step = new Duration(1);

        $chunker = new TimeRangeChunker(
            $now - 100,
            'start+90s',
            60,
            $step,
            $this->archive("RRA:MAX:0.5:1:400", $now, $step),
            $now,
        );

        $chunks = iterator_to_array($chunker->chunks(), false);

        $this->assertSame([
            ['start' => $now - 100, 'end' => $now - 40],
            ['start' => $now - 40, 'end' => $now - 10],
        ], $chunks);
    }

    public function testThrowsExceptionWhenChunkIsZero(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Chunk size must be greater than zero.');

        $now = 1778248927;
        $step = new Duration(10);

        $chunker = new TimeRangeChunker(
            $now - 50000,
            $now,
            0,
            $step,
            $this->archive("RRA:MAX:0.5:10:400", $now, $step),
            $now,
        );
    }

    public function testThrowsExceptionWhenStartAndEndAreCircular(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage("Circular time reference: start references 'end' but end also references 'start'.");

        $now = 1778248927;
        $step = new Duration(10);

        $chunker = new TimeRangeChunker(
            'end-100',
            'start+100',
            10,
            $step,
            $this->archive("RRA:MAX:0.5:10:400", $now, $step),
            $now,
        );

        $chunks = iterator_to_array($chunker->chunks(), false);
    }

    public function testThrowsExceptionWhenStartIsInvalid(): void
    {
        $start = 'last beore last';

        $this->expectException(TimeReferenceException::class);
        $this->expectExceptionMessage(TimeReferenceException::invalidTimeReference($start)->getMessage());

        $now = 1778248927;
        $step = new Duration(10);

        $chunker = new TimeRangeChunker(
            $start,
            $now,
            10,
            $step,
            $this->archive("RRA:MAX:0.5:10:400", $now, $step),
            $now,
        );

        $chunks = iterator_to_array($chunker->chunks(), false);
    }

    public function testThrowsExceptionWhenStartIsNegative(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('RRD time range must not contain timestamps before Unix epoch.');

        $now = 1778248927;
        $step = new Duration(10);

        $chunker = new TimeRangeChunker(
            '1960-01-01 00:00:00',
            $now,
            10,
            $step,
            $this->archive("RRA:MAX:0.5:10:400", $now, $step),
        );

        $chunks = iterator_to_array($chunker->chunks(), false);
    }

    public function testThrowsExceptionWhenEndIsNegative(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('RRD time range must not contain timestamps before Unix epoch.');

        $now = -10;
        $step = new Duration(10);

        $chunker = new TimeRangeChunker(
            '1980-01-01 00:00:00',
            '1960-01-01 00:00:00',
            10,
            $step,
            $this->archive("RRA:MAX:0.5:10:400", $now, $step),
        );

        $chunks = iterator_to_array($chunker->chunks(), false);
    }

    public function testThrowsExceptionWhenStartIsBigger(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('End time must be greater than start time after applying archive boundaries.');

        $now = 1778248927;
        $step = new Duration(10);

        $chunker = new TimeRangeChunker(
            $now,
            $now - 100,
            10,
            $step,
            $this->archive("RRA:MAX:0.5:10:400", $now, $step),
            $now,
        );

        $chunks = iterator_to_array($chunker->chunks(), false);
    }

    private function archive(string $definition, int $now, int|Duration $step, int $index = 0): RoundRobinArchive
    {
        $archive = new RoundRobinArchive($definition, $index);

        $archive->setFirstTimestamp(
            $now - $archive->getArchiveDurationInSeconds($step)
        );

        return $archive;
    }
}
