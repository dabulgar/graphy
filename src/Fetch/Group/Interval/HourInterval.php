<?php

namespace Davos\Graphy\Fetch\Group\Interval;

class HourInterval extends BaseInterval
{
    private int $baseSeconds = 3600;

    public function getInterval(int $timestamp, string $timezone = 'UTC'): array
    {
        $untilStart = $timestamp % ($this->baseSeconds * $this->intervals);

        $start = $timestamp - $untilStart;
        $end = $start + ($this->baseSeconds * $this->intervals);

        return ['start' => $start, 'end' => $end];
    }

}