# Examples

Each example should be self-contained in its own directory and include:

- `README.md` with the commands needed to seed and run it.
- `config.php` for Graphy configuration.
- one model file, such as `Power.php`.
- one data seeding script, such as `fill.php`.
- one public entry point, such as `index.html`.
- optional backend endpoints, such as `api.php`.

Generated RRD files should live in an example-local `rrd/` directory. That directory is ignored by Git.

## Watts

The watts example seeds a `power.rrd` file and displays it in a browser chart.

```bash
php examples/watts/fill.php
php -S 127.0.0.1:8080 -t examples/watts
```

Then open:

```text
http://127.0.0.1:8080/
```
