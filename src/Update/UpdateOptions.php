<?php

namespace Davos\Graphy\Update;

use Davos\Graphy\Concerns\InteractsWithFlags;
use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use Davos\Graphy\ValueObjects\DataSource;

class UpdateOptions
{
    use InteractsWithFlags;

    public const string TEMPLATE = '--template';
    public const string SKIP_PAST_UPDATES = '--skip-past-updates';
    public const string DAEMON = '--daemon';
    /** @var DataSource[] */
    private array $dataSources;
    private array $dsOrder = [];
    private array $validDs = [];
    private array $options = [];

    /**
     * @param array $dataSources
     * @param array $data
     * @param array $defaultFlags
     * @param array $flags
     */
    public function __construct(array $dataSources, array $data, array $defaultFlags, array $flags = [])
    {
        $this->dataSources = $dataSources;
        $this->generateDataSourcesOrder();

        $mergedFlags = $this->mergeFlags($defaultFlags, $flags);

        $this->includeFlags($mergedFlags);
        $this->appendUpdateData($data);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public static function getFlags(): array
    {
        return [
            self::TEMPLATE,
            self::SKIP_PAST_UPDATES,
            self::DAEMON,
        ];
    }


    /**
     * Resolves and prepares the data source (DS) order used for updates.
     *
     * - If a custom order is provided (via `--template`), it is parsed and validated
     *   against the list of valid data sources.
     * - If no custom order is provided, the default order is derived from the RRD
     *   data sources, excluding COMPUTE types.
     *
     * The resulting order is stored in `$this->dsOrder`, and valid data sources
     * are tracked in `$this->validDs`.
     *
     * @param string|null $dsOrder
     * @return void
     */
    private function generateDataSourcesOrder(?string $dsOrder = null): void
    {
        $this->dsOrder = [];

        if (is_string($dsOrder)) {
            $newDsOrder = explode(':', $dsOrder);
            foreach ($newDsOrder as $ds) {
                if (!in_array($ds, $this->validDs, true)) {
                    throw CommandDefinitionException::dataSourceNotFoundInModel($ds);
                }
                $this->dsOrder[] = $ds;
            }
            return;
        }

        foreach ($this->dataSources as $dataSource) {
            // Skip COMPUTE. It is automatically calculated from rrdtool
            if ($dataSource->getType() === 'COMPUTE') {
                continue;
            }

            $this->dsOrder[] = $dataSource->getName();
            $this->validDs[] = $dataSource->getName();
        }
    }

    /**
     * - Skips flags explicitly disabled via `false` or `null`.
     * - Adds each valid flag to the options list, OPTIONALLY followed by its value.
     *
     * Special handling for `--template`:
     * - If the value is a string, it is treated as a custom data source (DS) order
     *   and used to regenerate the internal DS ordering.
     * - If the value is `true`, the DS order is automatically generated from the
     *   internally resolved data sources and appended as a colon-separated string.
     *
     * Boolean flags act as switches and are included without a value.
     * Non-boolean values are appended as string arguments after the flag.
     *
     * @param array $allFlags Associative array of flags and their values
     */
    private function includeFlags(array $allFlags): void
    {
        $allowedFlags = self::getFlags();

        foreach ($allFlags as $flag => $value) {
            if (!in_array($flag, $allowedFlags, true)) {
                throw CommandDefinitionException::flagNotAllowed('update', $flag);
            }

            // do not use flag by just skipping...
            if ($value === false || $value === null) {
                continue;
            }

            $this->options[] = $flag;

            //  * SPECIAL CASE
            if ($flag === self::TEMPLATE) {
                if (is_string($value)) {
                    $this->generateDataSourcesOrder($value);
                }

                // By default --template flag will be true. Flag will be created from library.
                // This will ensure that every time we will use proper '--template' order
                if ($value === true) {
                    $value = implode(':', $this->dsOrder);
                }
            }

            if (!is_bool($value)) {
                $this->options[] = (string)$value;
            }
        }
    }

    /**
     * Appends update payload data in one of the supported input formats.
     *
     * Supported formats:
     * - A single batch of values, where the current time (`N`) is used automatically.
     * - A list of batch definitions in the form:
     *   `[['time' => ..., 'values' => [...]], ...]`
     * - An associative array of time => values pairs:
     *   `[timestamp|string => [...], ...]`
     *
     * The method detects the format automatically, validates batch structure,
     * validates time and values types, and delegates each normalized batch to
     * `appendBatch()`.
     *
     * @param array $data
     */
    private function appendUpdateData(array $data): void
    {
        if (count($data) === 0) {
            throw CommandDefinitionException::noDataProvidedForUpdate();
        }

        $firstKey = array_key_first($data);
        if ($firstKey === 0 && is_array($data[$firstKey])) {
            foreach ($data as $index => $batch) {
                if (!is_array($batch) || !array_key_exists('time', $batch) || !array_key_exists('values', $batch)) {
                    throw CommandDefinitionException::fromMessage(sprintf("Invalid batch format at index %s. Expected ['time' => ..., 'values' => [...]].", $index));
                }

                if (!is_int($batch['time']) && !is_string($batch['time'])) {
                    throw CommandDefinitionException::fromMessage(sprintf("Invalid time at index %s. Expected integer timestamp or string (e.g. 'N'),  got %s.", $index, gettype($batch['time'])));
                }

                if (!is_array($batch['values'])) {
                    throw CommandDefinitionException::fromMessage(sprintf("Invalid values at index %s. Expected array of data source values.", $index));
                }

                $this->appendBatch($batch['time'], $batch['values']);
            }
        } elseif (is_array($data[$firstKey])) {
            foreach ($data as $time => $values) {
                if (!is_array($values)) {
                    throw CommandDefinitionException::fromMessage(sprintf("Invalid values for time '%s'. Expected array of data source values.", $time));
                }

                $this->appendBatch($time, $values);
            }
        } else {
            $this->appendBatch('N', $data);
        }
    }

    /**
     * Builds a single update batch string for the given time and data source values.
     *
     * The batch is constructed according to the resolved data source order. For each
     * data source in `$this->dsOrder`, the corresponding value is appended if present.
     * If a value is missing, `U` is used as the unknown value placeholder.
     *
     * @param string|int $time
     * @param array $values
     * @return void
     */
    private function appendBatch(string|int $time, array $values): void
    {
        $option = (string)$time;
        foreach ($this->dsOrder as $ds) {
            if (array_key_exists($ds, $values)) {
                $option .= ":{$values[$ds]}";
            } else {
                $option .= ":U";
            }
        }

        $this->options[] = $option;
    }
}
