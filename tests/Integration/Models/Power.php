<?php

namespace Davos\Graphy\Tests\Integration\Models;

use Davos\Graphy\Builder\DS;
use Davos\Graphy\Builder\RRA;
use Davos\Graphy\RRD;
use Davos\Graphy\ValueObjects\Duration;

class Power extends RRD
{
    protected string|Duration $step = '10';

    protected string $start = '1699920000';

    protected function roundRobinArchives(): array
    {
        return [
            "30d_avg" => RRA::average()->steps(180)->rows(1440),
            "30d_min" => RRA::min()->steps(180)->rows(1440),
            "30d_max" => RRA::max()->steps(180)->rows(1440),
        ];
    }

    protected function dataSources(): array
    {
        return [
            DS::name('watts')->gauge()->heartbeat(1800),
        ];
    }
}
