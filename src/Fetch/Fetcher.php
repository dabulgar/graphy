<?php

namespace Davos\Graphy\Fetch;

use Davos\Graphy\Fetch\Group\Interval\BaseInterval;
use Davos\Graphy\Fetch\Group\TimestampWatermark;
use Davos\Graphy\Fetch\Group\TimeWindowedBucket;
use Davos\Graphy\Fetch\Group\WatermarkLevel;
use Davos\Graphy\Manager\Manager;
use Davos\Graphy\ValueObjects\Flag;
use Davos\Graphy\ValueObjects\RoundRobinArchive;

class Fetcher
{
    private ?string $timezone = null;
    private ?TimeWindowedBucket $timeWindowedBucket = null;
    private ?TimestampWatermark $labelsWatermark = null;
    private string $dateFormat;
    private mixed $nonBoundary;

    public function __construct(
        private string $file,
        private string $cf,
        private array $flags,
        private TimeRangeChunker $chunker,
        private Manager $manager,
        private RoundRobinArchive $archive,
    )
    {

    }
    
    private function rrdSeries(): \Generator
    {
        $resolution = $this->chunker->resolution->getDurationInSeconds();

        $timeRanges = $this->chunker->chunks();

        foreach ($timeRanges as $timeRange) {
            $defaultFlags = [
                new Flag(FetchOptions::RESOLUTION, $resolution),
                new Flag(FetchOptions::START, $timeRange['start']),
                new Flag(FetchOptions::END, $timeRange['end']),
                new Flag(FetchOptions::ALIGN_START, true),
            ];

            $options = new FetchOptions($this->cf, $defaultFlags, $this->flags);

            yield $this->manager->fetch($this->file, $options->getOptions());
        }
    }

    /**
     * @throws \DateInvalidTimeZoneException
     */
    public function cursor(): \Generator
    {
        $rrdSeries = $this->rrdSeries();

        foreach ($rrdSeries as $series) {
            $series->shiftFirstElement();

            foreach ($series as $timestamp => $datasets) {
                if (is_null($this->timeWindowedBucket)) {
                    yield $this->resolveTimestamp($timestamp) => $datasets;
                    continue;
                }

                $result = $this->timeWindowedBucket->attempt($timestamp);

                $this->timeWindowedBucket->push($datasets);

                if ($result === WatermarkLevel::HitBoundary) {
                    yield $this->resolveTimestamp($timestamp) => $this->timeWindowedBucket->flush();
                }
            }
        }
    }

    /**
     * @throws \DateInvalidTimeZoneException
     */
    public function get(): array
    {
        $data = ['timestamps' => [], 'datasets' => []];

        $iterator = $this->cursor();
        foreach ($iterator as $timestamp => $datasets) {
            $data['timestamps'][] = $timestamp;

            $names = array_keys($datasets);
            foreach ($names as $name) {
                $data['datasets'][$name][] = is_nan($datasets[$name]) ? null : $datasets[$name];
            }
        }

        return $data;
    }

    public function group(BaseInterval $interval): self
    {
        $tz = $this->resolveTimezone();

        $watermark = new TimestampWatermark(interval: $interval, timezone: $tz);

        $this->timeWindowedBucket = new TimeWindowedBucket(watermark: $watermark, archive: $this->archive);

        return $this;
    }

    public function labels(BaseInterval $interval, string $format = 'Y-m-d H:i:s', $nonBoundary = 'ts'): self
    {
        $tz = $this->resolveTimezone();

        $this->labelsWatermark = new TimestampWatermark(interval: $interval, timezone: $tz);
        $this->dateFormat = $format;
        $this->nonBoundary = $nonBoundary;

        return $this;
    }

    public function timezone(string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    private function resolveTimezone(): string
    {
        return $this->timezone ?? $this->manager->config->getTimezone();
    }

    /**
     * @throws \DateInvalidTimeZoneException
     */
    private function resolveTimestamp(int $timestamp): mixed
    {
        $tz = $this->resolveTimezone();

        if (is_null($this->labelsWatermark)) {
            return $timestamp;
        }

        $level = $this->labelsWatermark->level($timestamp);

        if ($level === WatermarkLevel::HitBoundary) {
            return (new \DateTime())
                ->setTimestamp($timestamp)
                ->setTimezone(new \DateTimeZone($tz))
                ->format($this->dateFormat);
        }

        return $this->resolveNonBoundary($timestamp, $tz);
    }

    /**
     * @param int $timestamp
     * @param string $tz
     * @return mixed
     * @throws \DateInvalidTimeZoneException
     */
    private function resolveNonBoundary(int $timestamp, string $tz): mixed
    {
        if (!is_string($this->nonBoundary)) {
            return $this->nonBoundary;
        }

        if ($this->nonBoundary === 'ts') {
            return $timestamp;
        }

        return (new \DateTime())
            ->setTimestamp($timestamp)
            ->setTimezone(new \DateTimeZone($tz))
            ->format($this->nonBoundary);
    }
}
