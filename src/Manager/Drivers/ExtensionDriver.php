<?php

namespace Davos\Graphy\Manager\Drivers;

use Davos\Graphy\Fetch\RrdSeries;

class ExtensionDriver implements DriverInterface
{
    public function create(string $file, array $options, int $permission): true|string
    {
        $res = \rrd_create($file, $options);
        if ($res === false) {
            return \rrd_error();
        }

        \chmod($file, $permission);

        return $res;
    }

    public function update(string $file, array $options = []): bool|string
    {
        $res = \rrd_update($file, $options);
        if ($res === false) {
            return \rrd_error();
        }

        return $res;
    }

    public function fetch(string $file, array $options = []): RrdSeries|string
    {
        $res = \rrd_fetch($file, $options);

        if ($res === false) {
            return \rrd_error();
        }

        return RrdSeries::fromExtensionResponse($res);
    }

    public function first(string $file, int $archive): int|string
    {
        $res = \rrd_first($file, $archive);
        if ($res === false) {
            return \rrd_error();
        }

        return $res;
    }
}
