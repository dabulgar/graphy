<?php

namespace Davos\Graphy\Fetch\Group\Aggregation;

interface AggregatorInterface
{
    public function aggregate(array $values, string $cf): float;
}