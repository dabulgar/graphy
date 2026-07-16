<?php

namespace Davos\Graphy\Fetch\Group;

use Davos\Graphy\Fetch\Group\Aggregation\AggregatorInterface;
use Davos\Graphy\Fetch\Group\Aggregation\ConsolidationAggregator;
use Davos\Graphy\ValueObjects\RoundRobinArchive;

class TimeWindowedBucket
{
    private array $datasets = [];

    public function __construct(
        private readonly TimestampWatermark $watermark,
        private readonly RoundRobinArchive $archive,
        private readonly AggregatorInterface $aggregator = new ConsolidationAggregator(),
    ) {
    }

    public function push(array $datasets): void
    {
        $names = array_keys($datasets);
        foreach ($names as $name) {
            $this->datasets[$name][] = $datasets[$name];
        }
    }

    public function attempt(int $timestamp): WatermarkLevel
    {
        return $this->watermark->level($timestamp);
    }

    public function flush(): array
    {
        $response = [];

        $keys = array_keys($this->datasets);
        foreach ($keys as $key) {
            $response[$key] = $this->aggregator->aggregate(
                $this->datasets[$key],
                $this->archive->getCf()
            );
        }

        $this->datasets = [];

        return $response;
    }
}
