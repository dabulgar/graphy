# Davos\Graphy

[![CI](https://github.com/dabulgar/graphy/actions/workflows/ci.yml/badge.svg)](https://github.com/dabulgar/graphy/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-%5E8.3-777BB4.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

RRDTool knows fixed steps. It does not know what a calendar day, week, month, or year is.

**Graphy** is a typed, fluent PHP API for creating, updating, and fetching RRDTool databases. Its main addition is calendar-aligned, timezone- and DST-aware grouping on top of existing RRD archives.

https://github.com/user-attachments/assets/c3192ec3-a9f0-4f44-8030-82793f785dd7

## Why Graphy?

Graphy gives you:

- model-based RRD definitions;
- fluent create, update, and fetch operations;
- automatic archive selection by consolidation function and resolution;
- bounded, chunked reads for large time ranges;
- calendar-aware grouping by second, minute, hour, day, week, month, or year;
- timezone- and DST-aware labels;
- typed exceptions instead of `false` or raw extension errors.

Graphy does not replace RRDTool. It provides a safer, higher-level PHP API around the `ext-rrd` extension.

## Table of contents

- [Installation](#installation)
- [Quick start](#quick-start)
- [Configuration](#configuration)
- [Defining a model](#defining-a-model)
- [Creating an RRD](#creating-an-rrd)
- [Updating data](#updating-data)
- [Fetching data](#fetching-data)
- [Grouping into calendar buckets](#grouping-into-calendar-buckets)
- [Data source reference](#data-source-reference)
- [Archive reference](#archive-reference)
- [Duration and time syntax](#duration-and-time-syntax)
- [Native RRDTool flags](#native-rrdtool-flags)
- [File organization with `path_mapper`](#file-organization-with-path_mapper)
- [Error handling](#error-handling)
- [Troubleshooting](#troubleshooting)
- [Examples](#examples)
- [Contributing](#contributing)
- [License](#license)

## Installation

```bash
composer require davos/graphy
```

### Requirements

- PHP 8.3 or newer;
- the [RRD PHP extension](https://www.php.net/manual/en/book.rrd.php);
- `ext-mbstring`;
- `ext-ctype`.

Verify that the RRD extension is loaded:

```bash
php -m | grep -i rrd
```

## Quick start

Configure Graphy once during application bootstrap:

```php
use Davos\Graphy\Manager\Factory\ManagerFactory;

ManagerFactory::configure([
    'path' => __DIR__ . '/storage/rrd',
    'path_mapper' => false,
    'driver' => 'ext',
    'permission' => 0644,
    'create_directories' => true,
    'directory_permission' => 0775,
    'timezone' => 'UTC',
]);
```

Define an RRD model:

```php
<?php

namespace App\Models;

use Davos\Graphy\Builder\DS;
use Davos\Graphy\Builder\RRA;
use Davos\Graphy\RRD;
use Davos\Graphy\ValueObjects\Duration;

final class Power extends RRD
{
    protected string|Duration $step = '1s';

    protected string $start = 'now-2y';

    protected function dataSources(): array
    {
        return [
            DS::name('watts')
                ->gauge()
                ->heartbeat(300)
                ->min(0)
                ->max(24000),
        ];
    }

    protected function roundRobinArchives(): array
    {
        return [
            'raw' => RRA::average()->steps(1)->rows(864000),
            '1m_avg' => RRA::average()->steps(60)->rows(129600),
            '1h_avg' => RRA::average()->steps(3600)->rows(13392),
            '1d_avg' => RRA::average()->steps(86400)->rows(3660),
        ];
    }
}
```

Create the file and write a value:

```php
Power::create('power');

Power::update('power', [
    'watts' => 42,
]);
```

Fetch the last day at one-minute resolution:

```php
$data = Power::fetch('power')
    ->average()
    ->resolution('1m')
    ->start('now-1d')
    ->end('now')
    ->run()
    ->get();
```

Graphy normalizes bare filenames to `.rrd`, so `power` and `power.rrd` address the same file.

## Configuration

All configuration keys shown below are required by the current configuration object.

```php
use Davos\Graphy\Manager\Factory\ManagerFactory;

ManagerFactory::configure([
    'path' => __DIR__ . '/storage/rrd',
    'path_mapper' => false,
    'driver' => 'ext',
    'permission' => 0644,
    'create_directories' => true,
    'directory_permission' => 0775,
    'timezone' => 'UTC',
]);
```

| Key | Type | Description |
|---|---|---|
| `path` | `string` | Base directory for relative RRD filenames. |
| `path_mapper` | `callable\|false` | Optionally maps a filename to a relative subpath. |
| `driver` | `string` | RRD backend. Currently only `ext` is supported. |
| `permission` | `int` | Mode applied to newly created `.rrd` files. |
| `create_directories` | `bool` | Creates missing parent directories when enabled. |
| `directory_permission` | `int` | Mode applied to newly created directories. |
| `timezone` | `string` | Default IANA timezone used for grouping and labels. |

Calling `ManagerFactory::configure()` again replaces the current configuration for subsequent operations.

## Defining a model

An RRD model describes:

- the primary data point step;
- the initial start time;
- one or more data sources;
- one or more round-robin archives.

The following RRDTool definition:

```bash
rrdtool create power.rrd \
  --start now-2y \
  --step 1 \
  DS:watts:GAUGE:300:0:24000 \
  RRA:AVERAGE:0.5:1:864000 \
  RRA:AVERAGE:0.5:60:129600 \
  RRA:AVERAGE:0.5:3600:13392 \
  RRA:AVERAGE:0.5:86400:3660
```

can be represented as:

```php
<?php

namespace App\Models;

use Davos\Graphy\Builder\DS;
use Davos\Graphy\Builder\RRA;
use Davos\Graphy\RRD;
use Davos\Graphy\ValueObjects\Duration;

final class Power extends RRD
{
    protected string|Duration $step = '1s';

    protected string $start = 'now-2y';

    protected function dataSources(): array
    {
        return [
            DS::name('watts')
                ->gauge()
                ->heartbeat(300)
                ->min(0)
                ->max(24000),
        ];
    }

    protected function roundRobinArchives(): array
    {
        return [
            'raw' => RRA::average()->steps(1)->rows(864000),
            '1m_avg' => RRA::average()->steps(60)->rows(129600),
            '1h_avg' => RRA::average()->steps(3600)->rows(13392),
            '1d_avg' => RRA::average()->steps(86400)->rows(3660),
        ];
    }
}
```

`dataSources()` and `roundRobinArchives()` are abstract methods and must be implemented by every model.

Archive keys are optional, but naming them makes `fromArchive()` calls clearer and less fragile than numeric indexes.

### Understanding archive resolution and retention

For every RRA:

```text
archive resolution = model step × RRA steps
archive retention  = archive resolution × RRA rows
```

With a model step of one second:

```php
'1m_avg' => RRA::average()
    ->steps(60)
    ->rows(129600),
```

the archive has:

```text
resolution = 1 second × 60 = 1 minute
retention  = 1 minute × 129600 = 90 days
```

## Creating an RRD

```php
Power::create('power');
```

Graphy resolves the model definition and calls the RRD extension with the equivalent create options.

The operation throws an exception when the command definition is invalid or RRDTool rejects the operation.

## Updating data

### One sample at the current time

```php
Power::update('power', [
    'watts' => 42,
]);
```

### Samples keyed by Unix timestamp

```php
Power::update('power', [
    1699920000 => ['watts' => 42],
    1699920300 => ['watts' => 45],
]);
```

### Explicit batch format

```php
Power::update('power', [
    [
        'time' => 1699920000,
        'values' => ['watts' => 42],
    ],
    [
        'time' => 1699920300,
        'values' => ['watts' => 45],
    ],
]);
```

When a defined data source is omitted from a sample, Graphy writes `U` (`unknown`) for that data source.

Use integer Unix timestamps for explicit sample times.

## Fetching data

### Select an archive by consolidation function and resolution

```php
$data = Power::fetch('power')
    ->average()
    ->resolution('1m')
    ->start('now-1d')
    ->end('now')
    ->run()
    ->get();
```

Available consolidation functions:

```php
->average()
->min()
->max()
->last()
```

The requested consolidation function and resolution must match an archive defined by the model.

Graphy:

1. finds the matching archive;
2. clamps the request to the archive's available retention range and to the current time;
3. aligns the request to the archive resolution;
4. splits large ranges into bounded chunks;
5. merges the chunks into one logical result.

The default chunk size is 10,000 data points. It can be changed per fetch:

```php
$fetcher = Power::fetch('power', chunkSize: 5_000)
    ->average()
    ->resolution('1m')
    ->start('now-1y')
    ->run();
```

### Select a named archive

A named archive supplies its own consolidation function, resolution, and full retention window:

```php
$data = Power::fetch('power')
    ->fromArchive('1h_avg')
    ->run()
    ->get();
```

`fromArchive()` uses the archive key or numeric index from `roundRobinArchives()`.

### Result shape

`get()` returns:

```php
/**
 * @var array{
 *     timestamps: list<int|string|null|false>,
 *     datasets: array<string, list<float|null>>
 * } $data
 */
```

Example:

```php
[
    'timestamps' => [
        1721347200,
        1721347260,
    ],
    'datasets' => [
        'watts' => [
            124.5,
            null,
        ],
    ],
]
```

RRD `NaN` values are converted to `null` by `get()`.

### Stream results with `cursor()`

Use `cursor()` when processing a large result row by row:

```php
$cursor = Power::fetch('power')
    ->average()
    ->resolution('1m')
    ->start('now-1y')
    ->run()
    ->cursor();

foreach ($cursor as $timestamp => $datasets) {
    $watts = $datasets['watts'];

    // Process or stream the row without building the complete get() array.
}
```

`cursor()` is lazy. It yields timestamps as keys and data-source maps as values.

Unlike `get()`, `cursor()` exposes raw numeric values from the fetch pipeline; normalize `NaN` yourself when needed.

## Grouping into calendar buckets

An RRD archive stores fixed-resolution points. A one-minute archive knows about 60-second steps, not calendar days or months.

Graphy's `group()` aggregates those points into calendar-aligned buckets after fetching.

```php
use Davos\Graphy\Fetch\Group\Interval\DayInterval;

$data = Power::fetch('power')
    ->average()
    ->resolution('1m')
    ->start('now-30d')
    ->end('now')
    ->timezone('Europe/Sofia')
    ->run()
    ->group(DayInterval::for(1))
    ->labels(DayInterval::for(1))
    ->get();
```

The timezone controls where calendar boundaries occur. This matters for local midnight and daylight-saving transitions.

### Available intervals

```php
use Davos\Graphy\Fetch\Group\Interval\SecondInterval;
use Davos\Graphy\Fetch\Group\Interval\MinuteInterval;
use Davos\Graphy\Fetch\Group\Interval\HourInterval;
use Davos\Graphy\Fetch\Group\Interval\DayInterval;
use Davos\Graphy\Fetch\Group\Interval\WeekInterval;
use Davos\Graphy\Fetch\Group\Interval\MonthInterval;
use Davos\Graphy\Fetch\Group\Interval\YearInterval;
```

Every interval accepts a positive multiplier:

```php
DayInterval::for(1);   // daily
DayInterval::for(7);   // every seven days
WeekInterval::for(2);  // every two weeks
```

Weeks start on Monday.

### `group()` and `labels()` are independent

`group()` aggregates values:

```php
$fetcher->group(DayInterval::for(1));
```

`labels()` formats timestamp keys:

```php
$fetcher->labels(
    DayInterval::for(1),
    'Y-m-d',
);
```

Labels can be used without grouping:

```php
$data = Power::fetch('power')
    ->average()
    ->resolution('1h')
    ->start('now-7d')
    ->timezone('Europe/Sofia')
    ->run()
    ->labels(DayInterval::for(1), 'Y-m-d')
    ->get();
```

For points that are not on a label boundary, the default behaviour is to preserve the Unix timestamp.

The third `labels()` argument changes that behaviour:

```php
// Preserve the Unix timestamp.
->labels(DayInterval::for(1), 'Y-m-d', 'ts')

// Return null for non-boundary points.
->labels(DayInterval::for(1), 'Y-m-d', null)

// Return false for non-boundary points.
->labels(DayInterval::for(1), 'Y-m-d', false)

// Format non-boundary points with another date format.
->labels(DayInterval::for(1), 'Y-m-d', 'H:i')
```

### Grouping constraints

The archive resolution must divide evenly into the requested grouping interval.

Valid examples:

```text
1-minute archive → day
30-minute archive → day
1-hour archive → week
```

Invalid example:

```text
7-hour archive → day
```

Invalid combinations throw `CommandDefinitionException` instead of producing silently misaligned results.

### Complete buckets

A grouped bucket is emitted when its calendar boundary is reached. A trailing bucket that has not reached its closing boundary is not included in the grouped result.

For example, a daily query ending at 15:00 may return completed days but omit the still-incomplete current day.

## Data source reference

Create a data source with:

```php
DS::name('watts')
```

Then choose exactly one data-source type.

| Method | RRD type | Typical use |
|---|---|---|
| `gauge()` | `GAUGE` | Direct measurements such as temperature, load, or power. |
| `counter()` | `COUNTER` | Continuously increasing counters with overflow handling. |
| `dcounter()` | `DCOUNTER` | Double-precision counter values. |
| `derive()` | `DERIVE` | Rates derived from increasing or decreasing values. |
| `dderive()` | `DDERIVE` | Double-precision derive values. |
| `absolute()` | `ABSOLUTE` | Counters reset after every read. |
| `compute($expression)` | `COMPUTE` | Values calculated from other data sources using RPN. |

For non-`COMPUTE` data sources, configure:

```php
DS::name('watts')
    ->gauge()
    ->heartbeat(300)
    ->min(0)
    ->max(24000);
```

- `heartbeat()` is the maximum accepted gap between updates before the value becomes unknown;
- `min()` and `max()` define accepted bounds;
- use RRD-compatible values such as `'U'` when a bound is unknown.

A computed data source uses an RPN expression:

```php
DS::name('kilowatts')
    ->compute('watts,1000,/');
```

## Archive reference

Create archives with one of:

```php
RRA::average()
RRA::min()
RRA::max()
RRA::last()
```

Then configure:

```php
RRA::average()
    ->xff(0.5)
    ->steps(60)
    ->rows(129600);
```

| Method | Meaning |
|---|---|
| `xff(float $value)` | XFiles factor: tolerated proportion of unknown primary data points. |
| `steps(int $steps)` | Primary data points consolidated into one archive row. |
| `rows(int $rows)` | Number of rows retained by the archive. |

The default XFiles factor is `0.5`.

## Duration and time syntax

### Duration values

Graphy accepts integers or strings containing a non-negative integer and an optional unit:

| Unit | Meaning |
|---|---|
| no suffix / `s` | seconds |
| `m` | minutes |
| `h` | hours |
| `d` | fixed 24-hour days |
| `w` | fixed seven-day weeks |
| `M` | fixed 31-day months |
| `y` | fixed 366-day years |

Examples:

```php
'30'
'30s'
'5m'
'2h'
'7d'
'2w'
'3M'
'1y'
```

`M` and `y` are fixed-duration aliases used for RRD calculations. They are not calendar-aware units. Calendar months and years are handled by `MonthInterval` and `YearInterval` during grouping.

### Fetch time references

`start()` and `end()` accept:

```php
->start(1721347200)          // Unix timestamp as an integer
->start('now-1d')
->start('end-30m')
->start('start+2h')
->start('2026-07-20 12:00:00')
->end('now')
```

Supported RRD-style anchors are:

```text
start
end
now
```

and can be combined with `+` or `-` and a duration.

> Pass Unix timestamps as integers. A digits-only string passed through the fluent fetch API is interpreted as a relative duration, not as an absolute Unix timestamp.

When `end()` is omitted, it defaults to `now`.

## Native RRDTool flags

Graphy exposes supported native options through `Flag` objects and operation-specific constants.

### Create flags

```php
use Davos\Graphy\Create\CreateOptions;
use Davos\Graphy\ValueObjects\Flag;

Power::create('power', [
    new Flag(CreateOptions::NO_OVERWRITE),
]);
```

Supported create constants:

```php
CreateOptions::STEP
CreateOptions::START
CreateOptions::NO_OVERWRITE
CreateOptions::DAEMON
CreateOptions::TEMPLATE
CreateOptions::FROM_SOURCE
```

Model-defined `step` and `start` are supplied automatically. User flags can override generated defaults where the operation permits it.

### Update flags

```php
use Davos\Graphy\Update\UpdateOptions;
use Davos\Graphy\ValueObjects\Flag;

Power::update(
    'power',
    ['watts' => 42],
    [
        new Flag(UpdateOptions::SKIP_PAST_UPDATES),
    ],
);
```

Supported update constants:

```php
UpdateOptions::TEMPLATE
UpdateOptions::SKIP_PAST_UPDATES
UpdateOptions::DAEMON
```

### Fetch flags

Fetch range and resolution options are normally generated by the fluent API and chunking pipeline.

Supported fetch constants are:

```php
FetchOptions::RESOLUTION
FetchOptions::START
FetchOptions::END
FetchOptions::ALIGN_START
FetchOptions::DAEMON
```

## File organization with `path_mapper`

For a small number of files, keep:

```php
'path_mapper' => false,
```

Every relative filename will be stored directly under the configured `path`.

For many dynamically named files, use `path_mapper` to shard them into deterministic subdirectories:

```php
ManagerFactory::configure([
    'path' => __DIR__ . '/storage/rrd',

    'path_mapper' => function (string $file): string {
        [$category, $id] = explode('_', $file, 2);
        $bucket = ((int) $id) % 5;

        return match ($category) {
            'zone' => "zones/{$bucket}/{$file}",
            'traffic' => "traffic/{$bucket}/{$file}",
            'client' => "clients/{$bucket}/{$file}",
            default => $file,
        };
    },

    'driver' => 'ext',
    'permission' => 0644,
    'create_directories' => true,
    'directory_permission' => 0775,
    'timezone' => 'UTC',
]);
```

Calling:

```php
Zone::create('zone_123');
```

resolves to:

```text
storage/rrd/zones/3/zone_123.rrd
```

The mapper receives a filename already normalized to end in `.rrd`.

Important behaviour:

- absolute paths bypass both `path` and `path_mapper`;
- relative mapper results are resolved under `path`;
- when `create_directories` is `true`, missing parent directories are created recursively;
- when `create_directories` is `false`, a missing directory causes `CommandDefinitionException`;
- new directories use `directory_permission`;
- newly created RRD files use `permission`.

A mapper should be deterministic: the same logical filename must always return the same path.

## Error handling

Graphy throws typed exceptions instead of returning `false` or raw error strings.

```php
use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use Davos\Graphy\Shared\Exceptions\ConfigException;
use Davos\Graphy\Shared\Exceptions\RrdToolExecutionException;

try {
    $data = Power::fetch('power')
        ->average()
        ->resolution('1m')
        ->start('now-1d')
        ->run()
        ->get();
} catch (CommandDefinitionException $exception) {
    // Invalid Graphy command definition:
    // missing archive, invalid range, invalid grouping resolution, etc.
} catch (RrdToolExecutionException $exception) {
    // The RRD extension or RRDTool rejected the generated command.
} catch (ConfigException $exception) {
    // Missing or invalid Graphy configuration.
}
```

| Exception | Meaning |
|---|---|
| `CommandDefinitionException` | The requested operation cannot be represented safely or validly. |
| `RrdToolExecutionException` | RRDTool rejected the generated command. |
| `ConfigException` | Configuration is missing or invalid. |

Value-object-specific exceptions may also be thrown for invalid durations or time references.

## Troubleshooting

### `ext-rrd` is missing

Check:

```bash
php -m | grep -i rrd
```

The extension must be available to the same PHP binary that runs Composer, tests, workers, or the web application.

### No matching archive was found

The requested consolidation function and resolution must match one model archive exactly.

For a model step of one second:

```php
RRA::average()->steps(60)->rows(1000)
```

has a resolution of 60 seconds, so request:

```php
->average()
->resolution('1m')
```

### Grouping throws a misalignment exception

Choose an archive resolution that divides evenly into the grouping interval.

For daily grouping, one minute, 30 minutes, or one hour are sensible choices. Seven hours is not.

### The current day is missing

Grouped output contains completed buckets. A current day, week, month, or year may be omitted until its closing boundary is reached.

### Values are `null`

RRDTool represents unknown values as `NaN`. `get()` converts those values to `null`.

Common causes include:

- missed updates beyond the data source heartbeat;
- values outside the configured minimum or maximum;
- insufficient known primary data points for the archive's XFiles factor.

## Examples

The [`examples/`](examples) directory contains runnable examples.

### Watts chart

The [`examples/watts`](examples/watts) example:

- defines a power model;
- creates and seeds a local RRD file;
- fetches and groups the data;
- serves a browser chart.

Run it with:

```bash
composer install
php examples/watts/fill.php
php -S 127.0.0.1:8080 -t examples/watts
```

Then open:

```text
http://127.0.0.1:8080
```

## Contributing

Install development dependencies:

```bash
composer install
```

Run the complete local quality suite:

```bash
composer quality
```

Or run checks separately:

```bash
composer cs:check
composer stan
composer test
```

Apply coding-style fixes:

```bash
composer cs:fix
```

Pull requests and pushes to `main` are checked by GitHub Actions for coding style, PHPStan, and PHPUnit.

When changing public behaviour, update or add:

- unit tests;
- integration tests when the RRD extension is involved;
- README examples and API notes.

## License

Graphy is released under the [MIT License](LICENSE).

MIT © David Ivanov
