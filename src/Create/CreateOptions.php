<?php

namespace Davos\Graphy\Create;

use Davos\Graphy\Concerns\InteractsWithFlags;
use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use Davos\Graphy\ValueObjects\DataSource;
use Davos\Graphy\ValueObjects\RoundRobinArchive;

class CreateOptions
{
    use InteractsWithFlags;

    public const string STEP = '--step';
    public const string START = '--start';
    public const string NO_OVERWRITE = '--no-overwrite';
    public const string DAEMON = '--daemon';
    public const string TEMPLATE = '--template';
    public const string FROM_SOURCE = '--source';
    
    private array $options = [];

    /**
     *  Builds the full list of CLI options required for creating an RRD database.
     *
     *  Responsibilities:
     *  - Merge default and user-provided flags (user flags override defaults)
     *  - Validate required flags are present and correctly typed
     *  - Convert flags, data sources, and archives into CLI-ready options
     *
     * @param DataSource[] $dataSources
     * @param RoundRobinArchive[] $roundRobinArchives
     * @param array $defaultFlags
     * @param array $flags
     * @throws CommandDefinitionException
     */
    public function __construct(array $dataSources, array $roundRobinArchives, array $defaultFlags, array $flags = [])
    {
        $mergedFlags = $this->mergeFlags($defaultFlags, $flags);

        $this->ensureRequiredFlagsExist([self::STEP, self::START], $mergedFlags, 'create');
        
        $this->includeFlags($mergedFlags, 'create');
        $this->includeDataSources($dataSources);
        $this->includeRoundRobinArchives($roundRobinArchives);
    }
    
    public function getOptions(): array
    {
        return $this->options;
    }
    
    public static function getFlags(): array
    {
        return [
            self::STEP,
            self::START,
            self::NO_OVERWRITE,
            self::DAEMON,
            self::TEMPLATE,
            self::FROM_SOURCE,
        ];
    }
    
    private function includeDataSources(array $dataSources): void
    {
        foreach ($dataSources as $dataSource) {
            $this->options[] = $dataSource->getDefinition();
        }
    }
    
    private function includeRoundRobinArchives(array $roundRobinArchives): void
    {
        foreach ($roundRobinArchives as $roundRobinArchive) {
            $this->options[] = $roundRobinArchive->getDefinition();
        }
    }
}
