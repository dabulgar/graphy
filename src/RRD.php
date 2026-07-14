<?php

namespace Davos\Graphy;

use Davos\Graphy\Builder\RRDBuilder;
use Davos\Graphy\Manager\Manager;
use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use Davos\Graphy\ValueObjects\DataSource;
use Davos\Graphy\ValueObjects\Duration;
use Davos\Graphy\ValueObjects\RoundRobinArchive;

/**
 * @method static RRDBuilder create(string $file, array $flags = [])
 * @method static RRDBuilder update(string $file, array $data, array $flags = [])
 * @method static RRDBuilder fetch(string $file, int $chunkSize = 10_000)
 * @method static RRDBuilder average()
 * @method static RRDBuilder min()
 * @method static RRDBuilder max()
 * @method static RRDBuilder last()
 * @method static RRDBuilder resolution(int|string $resolution)
 * @method static RRDBuilder start(int|string $start)
 * @method static RRDBuilder end(int|string $end)
 * @method static RRDBuilder fromArchive(int|string $rra)
 * @method static RRDBuilder run()
 */
abstract class RRD
{
    /** @var DataSource[] */
	protected array $dataSources = [];

    /** @var RoundRobinArchive[] */
	protected array $roundRobinArchives = [];
	protected string|Duration $step = '';
	protected string $start = '';
	
	protected Manager $manager;

    abstract protected function roundRobinArchives(): array;

    abstract protected function dataSources(): array;

    final public function __construct()
		{
        $this->loadDefinitions();

        $this->normalizeDataSources();
        $this->normalizeRoundRobinArchives();
        $this->normalizeStep();
        $this->normalizeStart();
    }

    /**
     * @param string $method
     * @param array<int, mixed> $arguments
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return (new RRDBuilder(new static()))->$method(...$arguments);
    }

    /**
     * @return DataSource[]
     */
    public function getDataSources(): array
	{
		return $this->dataSources;
	}

    /**
     * @return RoundRobinArchive[]
     */
	public function getRoundRobinArchives(): array
	{
		return $this->roundRobinArchives;
	}

    /**
     * @return Duration
     */
	public function getStep(): Duration
	{
		return $this->step;
	}

    /**
     * @return string
     */
	public function getStart(): string
	{
		return $this->start;
	}

    private function loadDefinitions(): void
    {
        $this->dataSources = $this->dataSources();
        $this->roundRobinArchives = $this->roundRobinArchives();
    }

    private function normalizeDataSources(): void
    {
        if (count($this->dataSources) === 0) {
            throw CommandDefinitionException::fromMessage("RRD must define at least one data source.");
        }

        $this->dataSources = array_map(
            fn ($item) => $item instanceof DataSource ? $item : new DataSource((string)$item),
            $this->dataSources
        );
    }

    private function normalizeRoundRobinArchives(): void
    {
        if (count($this->roundRobinArchives) === 0) {
            throw CommandDefinitionException::fromMessage("RRD must define at least one round-robin archive.");
        }

        $result = [];

        $index = 0;

        foreach ($this->roundRobinArchives as $key => $item) {
            $result[$key] = $item instanceof RoundRobinArchive
                ? $item
                : new RoundRobinArchive((string)$item, $index);

            $index++;
        }

        $this->roundRobinArchives = $result;
    }

    private function normalizeStep(): void
    {
        if ($this->step === '') {
            throw CommandDefinitionException::fromMessage("RRD must define a step duration.");
        }

        if (!($this->step instanceof Duration)) {
            $this->step = new Duration($this->step);
        }
    }

    private function normalizeStart(): void
    {
        if ($this->start === '') {
            $this->start = sprintf('now-%s', $this->getStep()->getDurationInSeconds());
        }
    }
}
