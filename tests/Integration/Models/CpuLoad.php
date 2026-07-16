<?php

namespace Davos\Graphy\Tests\Integration\Models;

use Davos\Graphy\Builder\DS;
use Davos\Graphy\Builder\RRA;
use Davos\Graphy\RRD;
use Davos\Graphy\ValueObjects\Duration;

class CpuLoad extends RRD
{
    protected string|Duration $step = '1';

    protected string $start = '1700000000';

    protected function roundRobinArchives(): array
    {
        return [
            "1h_avg" => RRA::average()->steps(1)->rows(3600),
            "1h_min" => RRA::min()->steps(1)->rows(3600),
            "1h_max" => RRA::max()->steps(1)->rows(3600),
            "3h_avg" => RRA::average()->steps(3)->rows(3600),
            "3h_min" => RRA::min()->steps(3)->rows(3600),
            "3h_max" => RRA::max()->steps(3)->rows(3600),
        ];
    }

    protected function dataSources(): array
    {
        return [
            DS::name('cpu_1')->gauge()->heartbeat(300)->min(0)->max(100),
            DS::name('cpu_2')->gauge()->heartbeat(300)->min(0)->max(100),
            DS::name('cpu_3')->gauge()->heartbeat(300)->min(0)->max(100),
        ];
    }
}
