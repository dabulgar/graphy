<?php

namespace Davos\Graphy\Fetch;

use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use Davos\Graphy\ValueObjects\Duration;
use Davos\Graphy\ValueObjects\Exceptions\TimeReferenceException;
use Davos\Graphy\ValueObjects\RoundRobinArchive;
use Davos\Graphy\ValueObjects\TimeReference;

class TimeRangeChunker
{
    private const int ARCHIVE_START_SAFE_MARGIN_STEPS = 2;
    private const int ARCHIVE_END_SAFE_MARGIN_STEPS = 1;

    public int $rows;

    public Duration $resolution;

    public function __construct(
        private readonly int|string        $start,
        private readonly int|string        $end,
        private readonly int               $chunkSize,
        private readonly Duration          $step,
        private readonly RoundRobinArchive $archive,
        private ?int $fixedNow = null, // for testing purpose
    )
    {
        if ($this->chunkSize <= 0) {
            throw CommandDefinitionException::fromMessage("Chunk size must be greater than zero.");
        }

        $this->resolution = new Duration($this->archive->getResolutionInSeconds($this->step));
    }

    public function chunks(): \Generator
    {
        $this->fixedNow = $this->fixedNow ?? time();

        $alignedTimestamps = $this->resolveAlignedBoundaries();
        $alignedStart = $alignedTimestamps['start'];
        $alignedEnd = $alignedTimestamps['end'];

        $this->assertValidRange($alignedStart, $alignedEnd);

        $this->rows = intdiv($alignedEnd - $alignedStart, $this->resolution->getDurationInSeconds());

        $maxDuration = $this->chunkSize * $this->resolution->getDurationInSeconds();

        $chunkStart = $alignedStart;

        while ($chunkStart < $alignedEnd) {
            $chunkEnd = min($chunkStart + $maxDuration, $alignedEnd);

            yield [
                'start' => $chunkStart,
                'end' => $chunkEnd,
            ];

            $chunkStart = $chunkEnd;
        }
    }

    /**
     * @return array{start: int, end: int}
     */
    private function resolveAlignedBoundaries(): array
    {
        $boundaries = $this->resolveBoundaries();

        /** @var TimeReference $start */
        $start = $boundaries['start'];

        /** @var TimeReference $end */
        $end = $boundaries['end'];

        $alignedStartTimestamp = $this->clampStartToArchiveBoundary(
            start: $start->alignToPreviousStep(duration: $this->resolution->getDurationInSeconds())
        );

        $alignedEndTimestamp = $this->clampEndToNow(
            end: $end->alignToPreviousStep(duration: $this->resolution->getDurationInSeconds())
        );

        return ['start' => $alignedStartTimestamp, 'end' => $alignedEndTimestamp];
    }

    /**
     * Resolves start and end into TimeReference objects in a safe order.
     *
     * The order matters because one boundary may reference the other:
     * - start may depend on end
     * - end may depend on start
     *
     * By default, end is resolved to "now" when it is not explicitly set.
     */
    private function resolveBoundaries(): array
    {
        $start = null;
        $end = null;

        $resolved = [
            'now' => $this->fixedNow,
        ];

        $order = $this->resolveOrder();

        foreach ($order as $boundary) {
            if ($boundary === 'end') {
                $end = new TimeReference($this->end, $resolved);
                $resolved['end'] = $end->getTimestamp();
                continue;
            }

            // start context
            $start = new TimeReference($this->start, $resolved);
            $resolved['start'] = $start->getTimestamp();
        }

        if (!($start instanceof TimeReference) || !($end instanceof TimeReference)) {
            throw TimeReferenceException::fromMessage(
                'Could not resolve start and end time references.'
            );
        }

        return ['start' => $start, 'end' => $end];
    }

    private function resolveOrder(): array
    {
        $startReferencesEnd = is_string($this->start) && str_contains($this->start, 'end');
        $endReferencesStart = is_string($this->end) && str_contains($this->end, 'start');

        if ($startReferencesEnd && $endReferencesStart) {
            throw CommandDefinitionException::fromMessage(
                "Circular time reference: start references 'end' but end also references 'start'."
            );
        }

        if ($endReferencesStart) {
            return ['start', 'end'];
        }

        return ['end', 'start'];
    }

    private function assertValidRange(int $start, int $end): void
    {
        if ($end <= $start) {
            throw CommandDefinitionException::fromMessage(
                'End time must be greater than start time after applying archive boundaries.'
            );
        }
    }

    private function clampStartToArchiveBoundary(int $start): int
    {
        $this->assertUnixTimestamp($start);

        $timeReference = new TimeReference($this->archive->getFirstTimestamp(), []);

        $baseResolution = $this->resolution->getDurationInSeconds();

        $archiveStart = $timeReference->alignToPreviousStep($baseResolution);
        // apply 2 pdp safe margin
        $archiveStart += $baseResolution * self::ARCHIVE_START_SAFE_MARGIN_STEPS;

        return max($archiveStart, $start);
    }

    private function clampEndToNow(int $end): int
    {
        $this->assertUnixTimestamp($end);

        $timeReference = new TimeReference($this->fixedNow, []);

        $baseResolution = $this->resolution->getDurationInSeconds();

        $archiveEnd = $timeReference->alignToPreviousStep($baseResolution);
        // remove one step to prevent last empty record from rrd
        $archiveEnd -= $baseResolution * self::ARCHIVE_END_SAFE_MARGIN_STEPS;

        return min($archiveEnd, $end);
    }

    private function assertUnixTimestamp(int $timestamp): void
    {
        if ($timestamp < 0) {
            throw CommandDefinitionException::fromMessage(
                'RRD time range must not contain timestamps before Unix epoch.'
            );
        }
    }
}