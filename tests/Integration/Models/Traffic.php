<?php

namespace Davos\Graphy\Tests\Integration\Models;

use Davos\Graphy\Builder\DS;
use Davos\Graphy\Builder\RRA;
use Davos\Graphy\RRD;
use Davos\Graphy\ValueObjects\Duration;

class Traffic extends RRD
{
    protected string|Duration $step = '1';

    protected string $start = '1699920000';

    protected function roundRobinArchives(): array
    {
        return [
            "1h_avg" => RRA::average()->steps(1)->rows(3600),
            "1h_min" => RRA::min()->steps(1)->rows(3600),
            "1h_max" => RRA::max()->steps(1)->rows(3600),
            "10h_avg" => RRA::average()->steps(10)->rows(3600),
            "10h_min" => RRA::min()->steps(10)->rows(3600),
            "10h_max" => RRA::max()->steps(10)->rows(3600),
        ];
    }

    protected function dataSources(): array
    {
        return [
            DS::name('traffic')->counter()->heartbeat(1800),
        ];
    }
}
