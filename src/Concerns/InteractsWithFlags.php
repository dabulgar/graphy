<?php

namespace Davos\Graphy\Concerns;

use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use Davos\Graphy\ValueObjects\Flag;

trait InteractsWithFlags
{
    /**
     *  Merges default flags with user-provided flags.
     *
     *  Rules:
     *  - All inputs must be instances of Flag
     *  - User flags override default flags when keys match
     *
     * @param array $defaultFlags
     * @param array $flags
     * @return array
     * @throws CommandDefinitionException
     */
    private function mergeFlags(array $defaultFlags, array $flags): array
    {
        $mergedFlags = [];

        foreach ($defaultFlags as $defaultFlag) {
            if (!($defaultFlag instanceof Flag)) {
                throw CommandDefinitionException::invalidFlagInstance();
            }
            $mergedFlags[$defaultFlag->getFlag()] = $defaultFlag->getValue();
        }
        foreach ($flags as $flag) {
            if (!($flag instanceof Flag)) {
                throw CommandDefinitionException::invalidFlagInstance();
            }
            // rewrite flag value
            $mergedFlags[$flag->getFlag()] = $flag->getValue();
        }

        return $mergedFlags;
    }

    /**
     * Ensures that all required flags are present and have scalar string/numeric values.
     *
     * @param array $requiredFlags
     * @param array $allFlags
     * @param string $action
     * @return void
     */
    private function ensureRequiredFlagsExist(array $requiredFlags, array $allFlags, string $action): void
    {
        foreach ($requiredFlags as $flag) {
            if (!array_key_exists($flag, $allFlags) || (!is_string($allFlags[$flag]) && !is_numeric($allFlags[$flag]))) {
                throw CommandDefinitionException::missingRequiredFlag($action, $flag);
            }
        }
    }


    /**
     *  Converts validated flags into CLI arguments and appends them to options.
     *
     *  Rules:
     *  - Only allowed flags are processed
     *  - null or false values are skipped
     *  - boolean true flags are added without value
     *  - non-boolean values are cast to string and appended
     *
     * @param array $allFlags
     * @param string $action
     * @return void
     */
    private function includeFlags(array $allFlags, string $action): void
    {
        $allowedFlags = self::getFlags();

        foreach ($allFlags as $flag => $value) {
            if (!in_array($flag, $allowedFlags, true)) {
                throw CommandDefinitionException::flagNotAllowed($action, $flag);
            }

            // skip flag
            if ($value === false || $value === null) {
                continue;
            }

            $this->options[] = $flag;

            // check if flag need value
            if (!is_bool($value)) {
                $this->options[] = (string)$value;
            }
        }
    }
}
