<?php

namespace Davos\Graphy\Fetch\Group\Interval;

class SecondInterval extends BaseInterval
{
    public function getInterval(int $timestamp, string $timezone = 'UTC'): array
    {
        $untilStart = $timestamp % $this->intervals;

        $start = $timestamp - $untilStart;
        $end = $start + $this->intervals;

        return ['start' => $start, 'end' => $end];
    }
}