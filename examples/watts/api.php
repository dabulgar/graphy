<?php

use Davos\Graphy\Fetch\Group\Interval\DayInterval;
use Davos\Graphy\Fetch\Group\Interval\HourInterval;
use Davos\Graphy\Fetch\Group\Interval\MinuteInterval;
use Davos\Graphy\Fetch\Group\Interval\MonthInterval;
use Davos\Graphy\Fetch\Group\Interval\WeekInterval;
use Davos\GraphyExample\Power;

require __DIR__ . '/bootstrap.php';

$allowedInputs = ['1s', '1m', '10m', '30m', '1d', '1M'];

$timezone = $_GET['timezone'] ?? 'UTC';
if (!in_array($timezone, timezone_identifiers_list(), true)) {
    $timezone = 'UTC';
}

$input = $_GET['input'] ?? '10m';
if (!in_array($input, $allowedInputs, true)) {
    $input = '10m';
}

$label = null;
$labelFormat = 'Y-m-d H:i:s';
$grouper = null;
$interval = null;

switch ($input) {
    case '1s':
        $resolution = '1s';
        $start = 'now-1d';
        $end = 'now';
        $label = HourInterval::for(2);
        $labelFormat = 'H:i';
        break;
    case '1m':
        $resolution = '1s';
        $start = 'now-5d';
        $end = 'now';
        $label = HourInterval::for(12);
        $labelFormat = 'M j, H:i';
        $interval = '1m';
        $grouper = MinuteInterval::for(1);
        break;
    case '10m':
        $resolution = '1s';
        $start = 'now-10d';
        $end = 'now';
        $label = DayInterval::for(1);
        $labelFormat = 'M j';
        $interval = '10m';
        $grouper = MinuteInterval::for(10);
        break;
    case '30m':
        $resolution = '30m';
        $start = 'now-30d';
        $end = 'now';
        $label = WeekInterval::for(1);
        $labelFormat = 'M j';
        $interval = '30m';
        break;
    case '1d':
        $resolution = '30m';
        $start = 'now-300d';
        $end = 'now';
        $label = MonthInterval::for(1);
        $labelFormat = 'M Y';
        $interval = '1d';
        $grouper = DayInterval::for(1);
        break;
    case '1M':
        $resolution = '30m';
        $start = 'now-583d';
        $end = 'now';
        $label = MonthInterval::for(1);
        $labelFormat = 'M Y';
        $interval = '1M';
        $grouper = MonthInterval::for(1);
        break;
}

header('Content-Type: application/json');

try {
    $fetcher = Power::fetch('power')
        ->average()
        ->resolution($resolution)
        ->start($start)
        ->end($end)
        ->run();

    $fetcher->timezone($timezone);
    if ($label) {
        $fetcher->labels($label, $labelFormat, 'ts');
    }
    if ($grouper) {
        $fetcher->group($grouper);
    }

    $data = $fetcher->get();
    $data['meta'] = [
        'timezone' => $timezone,
        'input' => $input,
        'interval' => $interval,
    ];

    echo json_encode($data);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
    ]);
}
