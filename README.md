## Davos\Graphy

---

RRDTool doesn't know what a "day" or a "week" is — only "N steps". **Graphy** does: a typed, fluent PHP API for RRDTool that adds real calendar-aligned, timezone-and-DST-aware grouping (day / week / month / year) on top of your existing archives, in addition to a clean model-based API for creating, updating, and fetching data.

https://github.com/user-attachments/assets/c3192ec3-a9f0-4f44-8030-82793f785dd7

### Installation

This library is installable via [Composer](https://getcomposer.org/):

```bash
composer require davos/graphy
```

### Requirements

Graphy requires **PHP 8.3** or higher and the **[RRD PHP extension](https://www.php.net/manual/en/book.rrd.php)** to be installed.

### Configuration

Before using Graphy, configure it once (e.g. on application bootstrap):

```php
use Davos\Graphy\Manager\Factory\ManagerFactory;

ManagerFactory::configure([
    'path' => __DIR__ . '/storage/rrd',   // base directory where .rrd files live
    'path_mapper' => false,               // or a callable(string $file): string to remap paths
    'driver' => 'ext',                    // only 'ext' (PHP RRD extension) is supported today
    'permission' => 0644,                 // file permissions for newly created .rrd files
    'create_directories' => true,         // auto-create missing directories
    'directory_permission' => 0775,
    'timezone' => 'UTC',                  // default timezone used for grouping/labels
]);
```

#### Organizing files with `path_mapper`

If you have a small, fixed set of `.rrd` files, `'path_mapper' => false` is all you need — every file lives directly under `path`. But once you're generating files dynamically (one RRD per zone, per client, per server, ...), dumping thousands of files into a single flat directory gets slow: most filesystems struggle to list, back up, or `ls` a directory once it holds tens of thousands of entries.

`path_mapper` lets you shard files into subdirectories, purely by naming convention, with zero manual folder setup. It's a `callable(string $file): string` that receives the filename you passed to `create()`/`update()`/`fetch()` (already normalized to end in `.rrd`) and returns the path — relative to `path` — where that file should actually live. Whatever nested path you return, Graphy creates it for you, recursively, the first time it's needed.

For example, calling `Zone::create('zone_123')` with this config:

```php
'path' => 'rrd',
'path_mapper' => function (string $file): string {
    [$category, $id] = explode('_', $file);
    $bucket = ((int) $id) % 5;

    return match ($category) {
        'zone' => 'zones/' . $bucket . '/' . $file,
        'traffic' => 'traffics/' . $bucket . '/' . $file,
        'client' => 'clients/' . $bucket . '/' . $file,
        'manager' => 'managers/' . $bucket . '/' . $file,
        'server' => 'servers/' . $bucket . '/' . $file,
        default => $file,
    };
},
'create_directories' => true,
'directory_permission' => 0775,
```

...resolves to `rrd/zones/3/zone_123.rrd` and, if `rrd/zones/3/` doesn't exist yet, Graphy creates the entire `rrd/`, `rrd/zones/`, and `rrd/zones/3/` chain in one go (`mkdir(..., recursive: true)` under the hood) — you never have to pre-create category or bucket folders yourself. New directories are created with `directory_permission`; new `.rrd` files get `permission`. Since the bucket is derived from the numeric ID (`id % 5` above), files for the same entity always land in the same, predictable subfolder — so a second `update()`/`fetch()` call for `zone_123` resolves to the exact same path without you tracking where anything was put.

A couple of things worth knowing:

- If you pass an **absolute path** (e.g. `/var/data/zone_123.rrd`) instead of a bare name, `path`/`path_mapper` are bypassed entirely — Graphy uses it as-is.
- If `create_directories` is `false` and the resolved directory doesn't exist, Graphy throws a `CommandDefinitionException` instead of creating it — useful in environments where the directory layout is provisioned externally (deploy scripts, infra-as-code) and an unexpected auto-created folder would actually indicate a bug.

### Defining a model

Graphy is built around **RRD models** — one PHP class per `.rrd` file layout. You describe the data sources and archives your data needs, and Graphy turns that into the equivalent `rrdtool create` call.

Here's the RRD you're modelling, as a raw `rrdtool` command, for reference:

```
rrdtool create power.rrd \
  --start now-2h --step 1 \
  DS:watts:GAUGE:300:0:24000 \
  RRA:AVERAGE:0.5:1:864000 \
  RRA:AVERAGE:0.5:60:129600 \
  RRA:AVERAGE:0.5:3600:13392 \
  RRA:AVERAGE:0.5:86400:3660
```

And here is the same definition as a Graphy model:

```php
<?php

namespace App\Models;

use Davos\Graphy\Builder\DS;
use Davos\Graphy\Builder\RRA;
use Davos\Graphy\RRD;
use Davos\Graphy\ValueObjects\Duration;

class Power extends RRD
{
    protected string|Duration $step = '1';

    protected string $start = 'now-2y';

    protected function dataSources(): array
    {
        return [
            DS::name('watts')->gauge()->heartbeat(300)->min(0)->max(24000),
        ];
    }

    protected function roundRobinArchives(): array
    {
        return [
            RRA::average()->steps(1)->rows(864000),
            RRA::average()->steps(60)->rows(129600),
            RRA::average()->steps(3600)->rows(13392),
            RRA::average()->steps(86400)->rows(3660),
        ];
    }
}
```

> **Note:** `dataSources()` and `roundRobinArchives()` are abstract methods on `RRD`, not properties — they must be implemented as methods on your model, as shown above. You can also give archives explicit keys (`"30d_avg" => RRA::average()->...`) so you can reference them later with `fromArchive()`.

### Create

```php
Power::create('power.rrd');
```

This resolves your model's `dataSources()`, `roundRobinArchives()`, `step` and `start`, and issues the equivalent `rrdtool create`. Extra native flags (e.g. `--template`) can be passed as the second argument.

### Update

```php
// single value, current time
Power::update('power.rrd', ['watts' => 42]);

// explicit timestamp => values
Power::update('power.rrd', [
    1699920000 => ['watts' => 42],
    1699920300 => ['watts' => 45],
]);

// explicit batch format
Power::update('power.rrd', [
    ['time' => 1699920000, 'values' => ['watts' => 42]],
    ['time' => 1699920300, 'values' => ['watts' => 45]],
]);
```

Any data source you don't provide a value for is written as `U` (unknown) — same as RRDTool's default behaviour.

### Fetch

At its simplest, fetch reads raw data back out at the resolution stored in an archive:

```php
$data = Power::fetch('power.rrd')
    ->average()             // consolidation function: average() | min() | max() | last()
    ->resolution('30m')     // must match the resolution of an existing archive
    ->start('now-1d')
    ->end('now')
    ->run()
    ->get();

// $data = ['timestamps' => [...], 'datasets' => ['watts' => [...]]]
```

Behind the scenes, Graphy finds the archive matching your `cf` + `resolution`, works out a safe time range (clamped to what the archive actually contains and to "now"), and — if that range is huge — pulls it back in chunks instead of one giant `rrd_fetch` call, so you don't blow up memory on multi-year queries.

If you'd rather not think in `cf` + `resolution` at all, you can fetch straight from a named archive:

```php
$data = Power::fetch('power.rrd')
    ->fromArchive('30d_avg')   // uses that archive's own cf, resolution and full retention window
    ->run()
    ->get();
```

### Grouping data into calendar buckets (the part that trips people up)

RRDTool archives store data at a **fixed** resolution — say, one point every 30 minutes. That's great for graphing, but often you actually want "one number per day" or "one number per week," not 48 points a day. RRDTool itself has no concept of "day" or "week" — it only knows "N steps." Graphy's `group()` adds that on top, entirely client-side, after fetching.

**Think of it like sorting mail into daily folders.** Graphy walks through the raw data points one by one and drops each one into a "bucket" for the day (or week/month/year) it belongs to. The moment a point lands exactly on a bucket's boundary, that bucket is sealed: all the values collected in it get combined (averaged / min'd / max'd / last'd — whichever consolidation function you picked) into a single number, and a new empty bucket starts for the next period.

```php
use Davos\Graphy\Fetch\Group\Interval\DayInterval;

$data = Power::fetch('power.rrd')
    ->average()
    ->resolution('30m')
    ->start('now-30d')
    ->end('now')
    ->timezone('Europe/Sofia')          // which calendar the buckets are drawn on
    ->run()
    ->group(DayInterval::for(1))        // 1 = every day; DayInterval::for(7) = every 7 days, etc.
    ->labels(DayInterval::for(1))       // optional: turn each bucket's timestamp into a readable string
    ->get();

// $data['timestamps'] = ['2024-05-01 00:00:00', '2024-05-02 00:00:00', ...]
// $data['datasets']['watts'] = [ daily average, daily average, ... ]
```

Available intervals: `SecondInterval`, `MinuteInterval`, `HourInterval`, `DayInterval`, `WeekInterval` (weeks start on Monday), `MonthInterval`, `YearInterval`. Each accepts a multiplier via `::for(n)` — e.g. `WeekInterval::for(2)` groups into fortnightly buckets.

`group()` and `labels()` are independent:
- `group()` actually **aggregates** raw points into one value per bucket.
- `labels()` only controls what the returned **timestamp key** looks like at each boundary — it can be used even without `group()`, if you just want readable dates on raw, ungrouped data. Points that don't fall on a label boundary keep their raw timestamp by default; pass `null`, `false`, or a date format as the third argument to `labels()` to customize non-boundary values.

**One rule to keep in mind:** the archive's resolution must divide evenly into the interval you group by (e.g. a 30-minute or 1-hour archive groups cleanly into days; a 7-hour archive does not). If it doesn't divide evenly, Graphy throws a `CommandDefinitionException` rather than silently producing misaligned buckets — better to fail loudly than to give you a chart with a wrong number on it.

### Error handling

Every operation throws a typed exception on failure instead of returning `false`/an error string:

- `CommandDefinitionException` — you asked Graphy to build an invalid command (e.g. no matching archive, empty update payload, misaligned grouping interval).
- `RrdToolExecutionException` — RRDTool itself rejected the command; carries the raw RRDTool error plus a preview of the command that failed, for easier debugging.
- `ConfigException` — `ManagerFactory::configure()` was called with invalid or missing config.

### Examples

The [`examples/`](examples) directory has runnable examples:

- [`watts`](examples/watts) — seeds a local `power.rrd` file and serves a browser chart backed by Graphy fetch/group/label calls.

```bash
composer install
php examples/watts/fill.php
php -S 127.0.0.1:8080 -t examples/watts
```


### License

MIT © David Ivanov
