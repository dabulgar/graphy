<?php

namespace Davos\Graphy\Fetch\Group\Interval;

use DateTimeImmutable;
use DateTimeZone;

final class DayInterval extends BaseInterval
{
    /**
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     */
    public function getInterval(int $timestamp, string $timezone = 'UTC'): array
    {
        $datetime = (new DateTimeImmutable("@{$timestamp}"))
                ->setTimezone(new DateTimeZone($timezone))
                ->setTime(0, 0, 0);

        return [
            'start' => $datetime->getTimestamp(),
            'end' => $datetime->modify("+ {$this->intervals} days")->getTimestamp(),
        ];
    }
}
