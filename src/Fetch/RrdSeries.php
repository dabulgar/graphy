<?php

namespace Davos\Graphy\Fetch;

use Traversable;

class RrdSeries implements \IteratorAggregate
{
    public int $start = 0;
    public int $step = 0;
    public array $datasets = [];
    public array $dataSources = [];

    public function timestampAt(int $index): int
    {
        return $this->start + ($index * $this->step);
    }

    public function addDataset($dsName, array $values): void
    {
        $this->datasets[$dsName] = $values;
    }

    public function shiftFirstElement(): void
    {
        $this->start += $this->step;

        $dataSources = array_keys($this->datasets);

        foreach ($dataSources as $dataSource) {
            array_shift($this->datasets[$dataSource]);
        }
    }

    public static function fromExtensionResponse(array $response): self
    {
        $rrdSeries = new self();

        $rrdSeries->start = $response['start'];
        $rrdSeries->step = $response['step'];

        $dataSources = array_keys($response['data']);
        $rrdSeries->dataSources = $dataSources;

        foreach ($dataSources as $dataSource) {
            $rrdSeries->addDataset($dataSource, array_values($response['data'][$dataSource]));
        }

        return $rrdSeries;
    }

    public function getIterator(): Traversable
    {
        $dataSources = array_keys($this->datasets);

        $count = count($this->datasets[$dataSources[0]]);

        for ($i = 0; $i < $count; $i++) {
            $row = [];

            foreach ($dataSources as $dataSource) {
                $row[$dataSource] = $this->datasets[$dataSource][$i];
            }

            yield $this->timestampAt($i + 1) => $row;
        }
    }
}
