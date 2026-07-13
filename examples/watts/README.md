# Watts Example

This example creates a local `power.rrd`, fills it with synthetic watt readings, and serves a small chart UI backed by `api.php`.

From the repository root:

```bash
php examples/watts/fill.php
php -S 127.0.0.1:8080 -t examples/watts
```

Open `http://127.0.0.1:8080/`.

The generated database is stored in `examples/watts/rrd/` and is ignored by Git.
