<?php

namespace Davos\Graphy\Fetch;

use Davos\Graphy\Concerns\InteractsWithFlags;
use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use Davos\Graphy\ValueObjects\RoundRobinArchive;

class FetchOptions
{
    use InteractsWithFlags;

    public const string RESOLUTION = '--resolution';
    public const string START = '--start';
    public const string END = '--end';
    public const string ALIGN_START = '--align-start';
    public const string DAEMON = '--daemon';

    private array $options = [];

    /**
     *  Builds the full list of CLI options
     *
     *  Responsibilities:
     *  - Merge default and user-provided flags (user flags override defaults)
     *  - Validate required flags are present and correctly typed
     *
     * @param string $cf
     * @param array $defaultFlags
     * @param array $flags
     */
    public function __construct(string $cf, array $defaultFlags, array $flags = [])
    {
        if (!in_array($cf, RoundRobinArchive::VALID_CF, true)) {
            throw CommandDefinitionException::invalidConsolidationFunction('fetch', $cf, RoundRobinArchive::VALID_CF);
        }

        $this->options[] = $cf;

        $mergedFlags = $this->mergeFlags($defaultFlags, $flags);

        $this->ensureRequiredFlagsExist([self::RESOLUTION, self::START], $mergedFlags, 'fetch');

        $this->includeFlags($mergedFlags, 'fetch');
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public static function getFlags(): array
    {
        return [
            self::RESOLUTION,
            self::START,
            self::END,
            self::ALIGN_START,
            self::DAEMON,
        ];
    }
}