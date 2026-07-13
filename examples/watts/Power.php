<?php

namespace Davos\GraphyExample;

use Davos\Graphy\Builder\DS;
use Davos\Graphy\Builder\RRA;
use Davos\Graphy\RRD;
use Davos\Graphy\ValueObjects\Duration;

class Power extends RRD
{
    protected string|Duration $step = '1';

    protected string $start = '1699920000';

    protected function roundRobinArchives(): array
    {
        return [
            "10d_avg" => RRA::average()->steps(1)->rows(864000),
            "10d_min" => RRA::min()->steps(1)->rows(864000),
            "10d_max" => RRA::max()->steps(1)->rows(864000),

            "583d_avg" => RRA::average()->steps(1800)->rows(28000),
            "583d_min" => RRA::min()->steps(1800)->rows(28000),
            "583d_max" => RRA::max()->steps(1800)->rows(28000),
        ];
    }

    protected function dataSources(): array
    {
        return [
            DS::name('watts')->gauge()->heartbeat(1800),
        ];
    }
}
