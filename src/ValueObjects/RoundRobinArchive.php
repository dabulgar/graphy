<?php

namespace Davos\Graphy\ValueObjects;

use Davos\Graphy\ValueObjects\Exceptions\RoundRobinArchiveDefinitionException;

class RoundRobinArchive
{
    public const VALID_CF = [
        'AVERAGE',
        'MIN',
        'MAX',
        'LAST',
    ];
    
    private string $definition;
    private string $cf;
    private float $xff;
    private Duration $steps;
    private Duration $rows;

    /** @var int RRA index */
    private int $index;
    private int $firstTimestamp;

    public function __construct(string $rraDefinition, int $index)
    {
        $this->definition = $rraDefinition;
        
        $arr = explode(':', $rraDefinition);
        $count = count($arr);
        
        if ($count < 4 || $count > 5) {
            throw RoundRobinArchiveDefinitionException::fromMessage(sprintf(
                'Invalid RRA definition "%s". Expected 4 or 5 parts separated by ":"',
                $rraDefinition,
            )
            );
        }
        
        if ($count === 5) {
            if (!in_array($arr[0], ['', 'RRA'], true)) {
                throw RoundRobinArchiveDefinitionException::fromMessage(
                    sprintf('Invalid RRA prefix "%s". Expected "RRA"', $arr[0])
                );
            }
            array_shift($arr);
        }
        
        $this->setCf($arr[0]);
        $this->setXff($arr[1]);
        $this->setSteps($arr[2]);
        $this->setRows($arr[3]);

        $this->setIndex($index);
    }
    
    public function getCf(): string
    {
        return $this->cf;
    }

    public function getXff(): float
    {
        return $this->xff;
    }
    
    public function getSteps(): int
    {
        return $this->steps->getDurationInSeconds();
    }

    public function getResolutionInSeconds(int|Duration $baseStep): int
    {
        $baseStepInSeconds = $baseStep instanceof Duration ? $baseStep->getDurationInSeconds() : $baseStep;

        return $this->getSteps() * $baseStepInSeconds;
    }

    public function getArchiveDurationInSeconds(int|Duration $baseStep): int
    {
        return $this->getResolutionInSeconds($baseStep) * $this->getRows();
    }

    /**
     *  Determines whether this Round Robin Archive can satisfy a fetch request.
     *
     *  An archive matches when:
     *  - its consolidation function (CF) matches the requested CF; and
     *  - its effective resolution, calculated from the RRD step and PDP-per-row
     *    ratio, matches the requested resolution.
     *
     * @param string $cf
     * @param int|Duration $resolution
     * @param int|Duration $baseStep
     * @return bool
     */
    public function matches(string $cf, int|Duration $resolution, int|Duration $baseStep): bool
    {
        $seconds = $resolution instanceof Duration ? $resolution->getDurationInSeconds() : $resolution;

        return $this->getCf() === $cf
            && $this->getResolutionInSeconds($baseStep) === $seconds;
    }

    private function setCf(string $cf): void
    {
        if (!in_array($cf, self::VALID_CF, true)) {
            throw RoundRobinArchiveDefinitionException::invalidCf($this->definition, self::VALID_CF);
        }

        $this->cf = $cf;
    }

    private function setXff(string $xff): void
    {
        $val = (float)$xff;
        if (!is_numeric($xff) || $val < 0 || $val > 1) {
            throw RoundRobinArchiveDefinitionException::invalidXff($this->definition);
        }

        $this->xff = $val;
    }

    private function setSteps(string $steps): void
    {
        $this->steps = new Duration($steps);
    }

    public function getRows(): int
    {
        return $this->rows->getDurationInSeconds();
    }
    
    private function setRows(string $rows): void
    {
        $this->rows = new Duration($rows);
    }
    
    public function getDefinition(): string
    {
        return sprintf('RRA:%s:%g:%d:%d', $this->getCf(), $this->getXff(), $this->getSteps(), $this->getRows());
    }

    public function setIndex(int $index): void
    {
        $this->index = $index;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function setFirstTimestamp(int $timestamp): void
    {
        $this->firstTimestamp = $timestamp;
    }

    public function getFirstTimestamp(): int
    {
        return $this->firstTimestamp;
    }
}
