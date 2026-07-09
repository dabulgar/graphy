<?php

namespace Davos\Graphy\Fetch\Group\Aggregation;

use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;

class ConsolidationAggregator implements AggregatorInterface
{
    public function aggregate(array $values, string $cf): float
    {
        $cf = strtoupper($cf);

        $values = array_filter($values, function ($value) {
            if (!is_int($value) && !is_float($value)) {
                throw CommandDefinitionException::fromMessage(
                    sprintf('Value must be an integer or a float. %s is given', get_debug_type($value))
                );
            }

            if (is_float($value) && is_nan($value)) {
                return false;
            }

            return true;
        });

        $count = count($values);

        if ($count === 0) {
            return match ($cf) {
                'AVERAGE', 'MIN', 'MAX', 'LAST' => NAN,
                default => throw CommandDefinitionException::fromMessage("Unsupported CF: {$cf}"),
            };
        }

        return match ($cf) {
            'AVERAGE' => array_sum($values) / $count,
            'MIN'     => (float) min($values),
            'MAX'     => (float) max($values),
            'LAST'    => (float) $values[array_key_last($values)],
            default   => throw CommandDefinitionException::fromMessage("Unsupported CF: {$cf}"),
        };
    }
}
