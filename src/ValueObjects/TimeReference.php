<?php

namespace Davos\Graphy\ValueObjects;

use Davos\Graphy\ValueObjects\Exceptions\TimeReferenceException;

class TimeReference
{
    private string|int $original;

    private int $timestamp;

    public function __construct(string|int $time, array $resolved)
    {
        $this->original = $time;

        if (is_int($time)) {
            $this->timestamp = $time;
        } elseif (ctype_digit($time)) {
            $this->timestamp = (int)$time;
        } elseif (strlen($time) < 3) {
            throw TimeReferenceException::timeTooShort($time);
        } else {
            $fromRelativeTimestamp = $this->handleFromRrdTime($time, $resolved);
            if ($fromRelativeTimestamp !== false) {
                $this->timestamp = $fromRelativeTimestamp;
                return;
            }

            $this->timestamp = $this->handleFromDateTime($time);
        }
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Aligns this timestamp down to the previous step boundary.
     *
     * Example:
     * 12:34 with 1h step -> 12:00
     * 12:00 with 1h step -> 12:00
     */
    public function alignToPreviousStep(int|Duration $duration): int
    {
        $resolution = $duration instanceof Duration
            ? $duration->getDurationInSeconds()
            : $duration;

        if ($resolution < 0) {
            throw TimeReferenceException::fromMessage(
                sprintf(
                    "Failed to align to previous step. Resolution must be bigger than 0, now is %s",
                    $resolution
                )
            );
        }

        return $this->timestamp - ($this->timestamp % $resolution);
    }

    public function getOriginal(): string|int
    {
        return $this->original;
    }

    private function handleFromRrdTime(string $time, array $resolved): int|bool
    {
        $isMatch = preg_match(
            pattern: "/^(?<anchor>start|end|now)(?:(?<op>[-+])(?<duration>\d+[smhdwMy]?))?$/",
            subject: $time,
            matches: $match,
            flags: PREG_UNMATCHED_AS_NULL
        );

        if (!$isMatch) {
            return false;
        }

        $anchor = $match['anchor'];
        $anchorTimestamp = $resolved[$anchor] ?? throw TimeReferenceException::missingAnchorInResolvedArray($anchor);

        $seconds = 0;
        if (!is_null($match['op']) && !is_null($match['duration'])) {
            $seconds = (new Duration($match['duration']))->getDurationInSeconds();
        }

        return match ($match['op']) {
            '+' => $anchorTimestamp + $seconds,
            '-' => $anchorTimestamp - $seconds,
            default => $anchorTimestamp,
        };
    }

    private function handleFromDateTime(string $time): int
    {
        try {
            return (new \DateTimeImmutable($time))->getTimestamp();
        } catch (\Throwable $throwable) {
            throw TimeReferenceException::invalidTimeReference($time, 0, $throwable);
        }
    }

    public static function normalize(int|string $timeReference, string $defaultAnchor): int|string
    {
        if (is_int($timeReference)) {
            return $timeReference;
        }

        if ($timeReference === '') {
            throw TimeReferenceException::invalidTimeReference('empty string');
        }

        if (ctype_digit($timeReference)) {
            return "{$defaultAnchor}-{$timeReference}s";
        }

        $isMatch = preg_match(
            pattern: "/^(?<anchor>start|end|now)?(?<op>[-+])?(?<duration>\d+[smhdwMy]?)?$/",
            subject: $timeReference,
            matches: $match,
            flags: PREG_UNMATCHED_AS_NULL
        );

        if (!$isMatch) {
            return $timeReference;
        }

        $anchor = $match['anchor'] ?? $defaultAnchor;
        $op = $match['op'];
        $duration = $match['duration'];

        if (is_null($op) && is_null($duration)) {
            return $anchor;
        }

        if (is_null($duration)) {
            throw TimeReferenceException::invalidTimeReference($timeReference);
        }

        $op ??= '-';

        $duration = new Duration($duration);

        return sprintf("%s%s%d%s", $anchor, $op, $duration->getDurationInSeconds(), 's');
    }
}
