<?php

namespace Davos\Graphy\Manager\Drivers;

use Davos\Graphy\Fetch\RrdSeries;

interface DriverInterface
{
    public function create(string $file, array $options, int $permission): bool|string;
    public function update(string $file, array $options = []): bool|string;
    public function fetch(string $file, array $options = []): RrdSeries|string;
    public function first(string $file, int $archive): int|string;
}