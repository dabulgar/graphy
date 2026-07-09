## Davos\Graphy

---

**Graphy** is a lightweight PHP library that sits between your application and RRDTool.

### Installation

This library is installable via [Composer](https://getcomposer.org/):

```bash
composer require davos/graphy
```

### Requirements

Graphy requires **PHP 8.3** or higher and the **RRD PHP extension** to be installed.

### Overview

Graphy is built around **RRD models**. You start by deciding what data to store and the intervals at which it should be recorded.

For the first example, we will use a standard **RRDTool** use case—tracking power consumption.

Below is a sample rrdtool create command that creates a database for storing power usage:

```
rrdtool
--start now-2h --step 1 \
DS:watts:GAUGE:300:0:24000 \
RRA:AVERAGE:0.5:1:864000 \
RRA:AVERAGE:0.5:60:129600 \
RRA:AVERAGE:0.5:3600:13392 \
RRA:AVERAGE:0.5:86400:3660
```

Now lets create a rrd model

```
<?php

namespace Davos\Graphy\Models;

use Davos\Graphy\RRD;
use Davos\Graphy\ValueObjects\DataSource;
use Davos\Graphy\ValueObjects\Duration;

class PowerRRDModel extends RRD
{

    protected array $dataSources = [
		"DS:watts:GAUGE:300:0:24000",
    ];

    protected array $roundRobinArchives = [
        "RRA:AVERAGE:0.5:1:864000",
        "RRA:AVERAGE:0.5:60:129600",
        "RRA:AVERAGE:0.5:3600:13392",
        "RRA:AVERAGE:0.5:86400:3660",
    ];

    protected string|Duration $step = '1';

    protected string $start = 'now-2y';
}

```
#### Create

