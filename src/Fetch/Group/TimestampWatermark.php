<?php

namespace Davos\Graphy\Fetch\Group;

use Davos\Graphy\Fetch\Group\Interval\BaseInterval;
use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;

class TimestampWatermark
{
    public int $start = 0;
    public int $end = 0;
    private bool $boundaryReached = true;

    public function __construct(
        private readonly BaseInterval $interval,
        private readonly string $timezone = 'UTC'
    ) {}

    public function level(int $timestamp): WatermarkLevel
    {
        if ($timestamp < $this->end) {
            return WatermarkLevel::Inside;
        }

        if ($timestamp === $this->end) {
            $this->boundaryReached = true;

            return WatermarkLevel::HitBoundary;
        }

        if ($this->boundaryReached === false) {
            throw CommandDefinitionException::fromMessage('Cannot determine next interval because previous interval end was not reached exactly.');
        }

        $this->boundaryReached = false;

        $intervals = $this->interval->getInterval($timestamp, $this->timezone);

        if ($timestamp === $intervals['start']) {
            // RRD fetch timestamps represent the end of the previous step interval.
            // If the timestamp is exactly on a bucket start boundary, it does not
            // belong to the new bucket. It closes the previous bucket instead.
            //
            // Example with step = 1800:
            //   timestamp 2026-05-21 00:00:00 represents
            //   2026-05-20 23:30:00 -> 2026-05-21 00:00:00
            //
            // Therefore, resolve the interval using timestamp - 1 so we get the bucket
            // that ended at this timestamp, not the bucket that starts at this timestamp.
            $intervals = $this->interval->getInterval($timestamp - 1, $this->timezone);
            $this->start = $intervals['start'];
            $this->end = $intervals['end'];

            $this->boundaryReached = true;

            return WatermarkLevel::HitBoundary;
        }

        $this->start = $intervals['start'];
        $this->end = $intervals['end'];

        return WatermarkLevel::Started;
    }
}
