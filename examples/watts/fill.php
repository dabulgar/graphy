<?php

use Davos\GraphyExample\Power;

$config = require __DIR__ . '/bootstrap.php';

$file = 'power';
$fullPath = $config['path'] . DIRECTORY_SEPARATOR . 'power.rrd';

if (file_exists($fullPath)) {
    unlink($fullPath);
}

Power::create($file);

$start = time() - 60000000;
$chunks = [];

for ($i = 1; $i <= 60000000; $i++) {
    $start++;

    $coefficient = ($i % 10) + 1;
    $random = \rand(1000, 10000) * $coefficient;

    $chunks[$start] = ['watts' => $random];

    if (count($chunks) === 1000) {
        Power::update($file, $chunks);
        $chunks = [];
    }
}
