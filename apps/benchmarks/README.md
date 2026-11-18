# UX benchmarks

A Symfony 8.2 app that measures the rendering cost of the UX packages. It has one page per package (Css, TwigComponent, LiveComponent, Icons, Map), each pushed to an extreme size, and LiveComponent is also measured as the POST re-renders the browser sends. FrankenPHP serves the app in classic then in worker mode, `ab` (ApacheBench) loads it, and the results go to `RESULTS.md`.

Only the server-side rendering is measured: no page loads a stylesheet or a script, and building the CSS is not measured.

## The pages

The sizes are constants, so that `RESULTS.md` stays comparable from one run to the next. The home page `/` links to every page.

| Route                       | Package        | What it renders                                                                                                                     |
| --------------------------- | -------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| `/css/tailwind`             | Css (baseline) | The shadcn dashboard with 25 rows and 389 components, styled with `html_cva()` and the `tailwind_classes` filter.                   |
| `/css/ux-css`               | Css            | The same dashboard with 25 rows and 389 components, styled with `css()` and `cva()`.                                                |
| `/twig-component/dashboard` | TwigComponent  | The dashboard with 500 rows and 3,560 class-backed components (typed props, `#[ExposeInTemplate]`, `#[PreMount]`, blocks, nesting). |
| `/live/dashboard`           | LiveComponent  | A stats card, a table of 100 rows with 100 live children (one `FeaturedToggle` per row), and a form with validation.                |
| `/live/props`               | LiveComponent  | One component with 52 LiveProps of every type, to measure dehydration and the checksum.                                             |
| `/icons/grid`               | Icons          | 2,000 icons over 50 local Lucide icons, half with `ux_icon()` and half with `<twig:ux:icon>`, with varied attributes.               |
| `/map/leaflet`              | Map (Leaflet)  | 2,000 markers with an info window each, 100 polygons, 50 polylines and 20 circles.                                                  |
| `/map/google`               | Map (Google)   | The same map as `/map/leaflet`, rendered by the Google bridge.                                                                      |

The 8 live scenarios are `live/table-search`, `live/table-select`, `live/table-bulk-delete`, `live/table-next-page`, `live/form-validate` (which answers 422 on purpose), `live/featured-toggle`, `live/stats-refresh` and `live/props-update`. Each one POSTs the real LiveComponent re-render request (`/_components/...`, with the same headers and body as the browser), built from the HTML of the rendered page.

## Run it

```shell
symfony composer install
symfony php ../../link .
bin/install-frankenphp
bin/bench
```

`bin/install-frankenphp` downloads the static FrankenPHP binary (v1.13.5 by default, or the version given as argument) from the GitHub releases to `var/frankenphp/`, and checks its SHA-256. It supports macOS arm64 and Linux x86_64/aarch64.

`bin/bench` needs `ab`, which ships with macOS. On Linux, install `apache2-utils`. It writes the results to `RESULTS.md` and prints them. The default run takes about 25 minutes, plus the warmups. The options:

- `--rounds 3`: the number of rounds per mode.
- `--seconds 15`: the seconds of load per target and round.
- `--concurrency 4`: the concurrent requests of `ab`.
- `--mode both`: `classic`, `worker` or `both`.
- `--only <prefix>`: only the targets whose name starts with the prefix, repeatable. For example `--only css`, `--only live` or `--only live/table-select`.
- `--yes`: run even if the machine is not idle, without asking.
- `--port 8180`: the port of FrankenPHP.

`bin/bench --check --url <url>` does not benchmark anything. It runs against an already running server and checks that every page answers with the expected status and the same HTML on two requests, that every page holds its minimum counts, and that every live scenario builds its request and gets the expected status with the same HTML on two requests. CI runs it against `symfony server:start`, see `.github/workflows/app-benchmarks.yaml`.

## What `bin/bench` does

1. Checks that the FrankenPHP binary is there (else it points to `bin/install-frankenphp`) and that `ab` is in the `PATH`.
2. Checks that the machine is idle: if the load average is above half the cores or a process uses more than 5% of the CPU, it lists them and asks for a confirmation, unless `--yes` is given or the command is not interactive.
3. Dumps an authoritative class map of the autoloader and clears the `prod` cache. It restores the regular autoloader when it ends, including on Ctrl+C.
4. For each mode, classic then worker:
    1. Starts FrankenPHP, configured by `Caddyfile` and `frankenphp.ini`, which follows the [Symfony performance checklist](https://symfony.com/doc/current/performance.html): OPcache without timestamp checks, class preloading, realpath cache, and assertions off.
    2. Checks every target: the expected status and the same HTML on two requests; a page must also render the same HTML as in the previous mode.
    3. Sends 200 warmup requests per target.
    4. Runs the rounds, with the order of the targets reversed on even rounds.
5. Writes `RESULTS.md`: the median and the range of the requests per second, the latency percentiles, the errors, the size of each page and what it holds, and the load average before and after the run.
6. For Css, compares UX CSS with Tailwind CSS in one sentence per mode, with the spread of the rounds.

## Add a target

Add an entry to `TargetProvider::targets()`. Its `minimumCounts` keys must be ones that `PageStats::counts()` computes from the HTML of the page:

- `components`: the elements with a `data-slot` attribute.
- `rows`: the `tbody tr` elements.
- `icons`: the `svg` elements.
- `markers`: the markers in the `*-map-markers-value` attribute of the Map.
- `live_children`: the elements with a `data-live-fingerprint-value` attribute, which are the nested live components.

A live scenario names a page and the component rendered on it (its `data-live-name-value`), from which `LiveRequestBuilder` builds the POST request. To validate the target, run `bin/bench --check --url <url>` against a running server.
