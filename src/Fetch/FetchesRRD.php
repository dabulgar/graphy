<?php

namespace Davos\Graphy\Fetch;

use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use Davos\Graphy\Shared\Support\FileManager;
use Davos\Graphy\ValueObjects\Duration;
use Davos\Graphy\ValueObjects\RoundRobinArchive;
use Davos\Graphy\ValueObjects\TimeReference;

trait FetchesRRD
{
    private string $file;
    private int $chunkSize;

    private string $cf;
    private Duration $resolution;
    private int|string $start;
    private int|string $end;
    private ?string $timezone = null;
    private RoundRobinArchive $roundRobinArchive;

    public function fetch(string $file, int $chunkSize = 10_000): self
    {
        if ($chunkSize < 1) {
            throw CommandDefinitionException::fromMessage(
                'Chunk size must be greater than 0.'
            );
        }

        $this->file = FileManager::ensureRrdExtension($file);
        $this->chunkSize = $chunkSize;

        return $this;
    }

    public function average(): self
    {
        $this->cf = 'AVERAGE';

        return $this;
    }

    public function min(): self
    {
        $this->cf = 'MIN';

        return $this;
    }

    public function max(): self
    {
        $this->cf = 'MAX';

        return $this;
    }

    public function last(): self
    {
        $this->cf = 'LAST';

        return $this;
    }

    public function resolution(int|string $resolution): self
    {
        $this->resolution = new Duration($resolution);

        return $this;
    }

    public function start(int|string $start): self
    {
        $this->start = TimeReference::normalize($start, 'end');

        return $this;
    }

    public function end(int|string $end): self
    {
        $this->end = TimeReference::normalize($end, 'now');

        return $this;
    }

    public function timezone(string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function fromArchive(int|string $rra): self
    {
        $archives = $this->model->getRoundRobinArchives();

        if (!isset($archives[$rra])) {
            throw CommandDefinitionException::fromMessage(sprintf(
                "RRA index '%s' does not exist. Available indices: %s",
                $rra,
                implode(', ', array_keys($archives))
            ));
        }

        $archive = $archives[$rra];

        $step = $this->model->getStep();

        $this->cf = $archive->getCf();
        $this->resolution = new Duration($archive->getResolutionInSeconds($step));
        $this->end = 'now';
        $this->start = sprintf("end-%d", $archive->getArchiveDurationInSeconds($step));

        return $this;
    }

    public function run(): Fetcher
    {
        $this->ensureFetchIsCalled();

        if (!isset($this->cf)) {
            throw CommandDefinitionException::fromMessage("One of consolidation functions (average(), min(), max(), last()) must be called before run()");
        }

        if (!isset($this->resolution)) {
            throw CommandDefinitionException::fromMessage("resolution() must be called before run()");
        }

        if (!isset($this->start)) {
            throw CommandDefinitionException::fromMessage("start() must be called before run()");
        }

        if (!isset($this->end)) {
            $this->end = 'now';
        }

        $this->resolveMatchingArchive();

        $firstTimestamp = $this->manager->first($this->file, $this->roundRobinArchive->getIndex());
        $this->roundRobinArchive->setFirstTimestamp($firstTimestamp);

        $timeRange = new TimeRangeChunker(
            $this->start,
            $this->end,
            $this->chunkSize,
            $this->model->getStep(),
            $this->roundRobinArchive,
        );

        return new Fetcher($this->file, $this->cf, [], $this->resolveTimezone(), $timeRange, $this->manager, $this->roundRobinArchive);
    }

    private function resolveTimezone(): string
    {
        return $this->timezone ?? $this->manager->config->getTimezone();
    }

    private function resolveMatchingArchive(): void
    {
        $step = $this->model->getStep();

        foreach ($this->model->getRoundRobinArchives() as $roundRobinArchive) {
            if ($roundRobinArchive->matches($this->cf, $this->resolution, $step)) {
                $this->roundRobinArchive = $roundRobinArchive;
                return;
            }
        }

        throw CommandDefinitionException::fromMessage(
            sprintf(
                "No Round Robin Archive found for CF '%s' with resolution '%d seconds'.",
                $this->cf,
                $this->resolution->getDurationInSeconds()
            )
        );
    }

    private function ensureFetchIsCalled(): void
    {
        if (!isset($this->file)) {
            throw CommandDefinitionException::fromMessage("fetch() must be called first");
        }
    }
}
