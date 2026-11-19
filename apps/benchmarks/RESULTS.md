# Results

Written by `bin/bench` on 2026-11-18 23:18 UTC, from symfony/ux 0ae2e4df989.

- FrankenPHP v1.13.5 with PHP 8.5.12, static binary, opcache and preloading on (see `frankenphp.ini`)
- Symfony 8.2.0-BETA2, Twig 3.30.0, tales-from-a-dev/twig-tailwind-extra 1.5.0, `prod` environment
- Apple M1 Pro, 10 cores, Darwin 27.2.0; `ab` runs on the same machine
- `ab -k -c 4`: 200 warmup requests per target, then 3 rounds of 15 seconds per target
- Load average: 2.98 before the run, 3.83 after (1 minute)

## Css

| Page                           | HTML     | `class` attributes | Classes | Unique classes |
| ------------------------------ | -------- | ------------------ | ------- | -------------- |
| Tailwind CSS (`/css/tailwind`) | 164.5 KB | 505                | 6035    | 236            |
| UX CSS (`/css/ux-css`)         | 174.8 KB | 505                | 5689    | 227            |

| Mode    | Target                         | req/s (median) | req/s (range) | p50  | p95  | p99  | Errors |
| ------- | ------------------------------ | -------------- | ------------- | ---- | ---- | ---- | ------ |
| classic | Tailwind CSS (`/css/tailwind`) | 494.2          | 492.4-501.7   | 8 ms | 9 ms | 9 ms | 0      |
| classic | UX CSS (`/css/ux-css`)         | 531.9          | 526.8-533.9   | 8 ms | 8 ms | 8 ms | 0      |
| worker  | Tailwind CSS (`/css/tailwind`) | 575.6          | 574.9-577.0   | 7 ms | 7 ms | 8 ms | 0      |
| worker  | UX CSS (`/css/ux-css`)         | 607.0          | 605.7-612.5   | 7 ms | 7 ms | 7 ms | 0      |

- classic mode: UX CSS serves +7.6% requests per second compared to Tailwind CSS; the rounds of a page spread over up to 1.9%.
- worker mode: UX CSS serves +5.5% requests per second compared to Tailwind CSS; the rounds of a page spread over up to 1.1%.

## TwigComponent

| Target                     | HTML      | Holds                              |
| -------------------------- | --------- | ---------------------------------- |
| `twig-component/dashboard` | 1163.9 KB | 3,560 components, 500 rows, 1 icon |

| Mode    | Target                     | req/s (median) | req/s (range) | p50   | p95   | p99   | Errors |
| ------- | -------------------------- | -------------- | ------------- | ----- | ----- | ----- | ------ |
| classic | `twig-component/dashboard` | 80.3           | 80.1-81.5     | 46 ms | 62 ms | 72 ms | 0      |
| worker  | `twig-component/dashboard` | 81.8           | 81.5-82.7     | 45 ms | 60 ms | 70 ms | 0      |

## LiveComponent

| Target           | HTML     | Holds                                                  |
| ---------------- | -------- | ------------------------------------------------------ |
| `live/dashboard` | 596.2 KB | 1,368 components, 100 rows, 7 icons, 100 live children |
| `live/props`     | 3.1 KB   | -                                                      |

| Mode    | Target                   | req/s (median) | req/s (range)   | p50   | p95   | p99   | Errors |
| ------- | ------------------------ | -------------- | --------------- | ----- | ----- | ----- | ------ |
| classic | `live/dashboard`         | 113.1          | 111.4-113.4     | 32 ms | 49 ms | 58 ms | 0      |
| classic | `live/props`             | 2640.7         | 2593.2-2646.2   | 1 ms  | 2 ms  | 2 ms  | 0      |
| classic | `live/table-search`      | 130.9          | 129.3-136.8     | 27 ms | 42 ms | 54 ms | 0      |
| classic | `live/table-select`      | 161.1          | 156.4-161.1     | 24 ms | 36 ms | 39 ms | 0      |
| classic | `live/table-bulk-delete` | 160.1          | 155.9-161.2     | 24 ms | 35 ms | 41 ms | 0      |
| classic | `live/table-next-page`   | 120.0          | 120.0-123.5     | 30 ms | 47 ms | 57 ms | 0      |
| classic | `live/form-validate`     | 1151.4         | 1141.2-1156.6   | 3 ms  | 4 ms  | 4 ms  | 0      |
| classic | `live/featured-toggle`   | 3911.3         | 3876.7-3913.0   | 1 ms  | 1 ms  | 1 ms  | 0      |
| classic | `live/stats-refresh`     | 1277.2         | 1270.3-1285.0   | 3 ms  | 4 ms  | 4 ms  | 0      |
| classic | `live/props-update`      | 1810.1         | 1789.4-1824.4   | 2 ms  | 3 ms  | 3 ms  | 0      |
| worker  | `live/dashboard`         | 126.8          | 123.3-127.4     | 28 ms | 43 ms | 55 ms | 0      |
| worker  | `live/props`             | 5810.9         | 5783.2-5878.5   | 1 ms  | 1 ms  | 1 ms  | 0      |
| worker  | `live/table-search`      | 143.7          | 134.9-146.8     | 24 ms | 41 ms | 53 ms | 0      |
| worker  | `live/table-select`      | 176.7          | 174.4-178.9     | 21 ms | 34 ms | 42 ms | 0      |
| worker  | `live/table-bulk-delete` | 175.5          | 174.8-176.7     | 21 ms | 34 ms | 39 ms | 0      |
| worker  | `live/table-next-page`   | 129.8          | 127.3-137.2     | 27 ms | 46 ms | 58 ms | 0      |
| worker  | `live/form-validate`     | 1702.4         | 1675.7-1771.7   | 2 ms  | 3 ms  | 3 ms  | 0      |
| worker  | `live/featured-toggle`   | 10578.5        | 10004.6-10590.3 | 0 ms  | 0 ms  | 1 ms  | 0      |
| worker  | `live/stats-refresh`     | 3596.8         | 3065.9-3619.1   | 1 ms  | 1 ms  | 1 ms  | 0      |
| worker  | `live/props-update`      | 3059.3         | 2765.2-3195.6   | 1 ms  | 2 ms  | 2 ms  | 0      |

## Icons

| Target       | HTML     | Holds       |
| ------------ | -------- | ----------- |
| `icons/grid` | 728.5 KB | 2,000 icons |

| Mode    | Target       | req/s (median) | req/s (range) | p50  | p95   | p99   | Errors |
| ------- | ------------ | -------------- | ------------- | ---- | ----- | ----- | ------ |
| classic | `icons/grid` | 291.8          | 260.7-315.9   | 9 ms | 32 ms | 42 ms | 0      |
| worker  | `icons/grid` | 268.6          | 248.7-272.1   | 9 ms | 36 ms | 46 ms | 0      |

## Map

| Target        | HTML      | Holds         |
| ------------- | --------- | ------------- |
| `map/leaflet` | 1004.6 KB | 2,000 markers |
| `map/google`  | 1004.7 KB | 2,000 markers |

| Mode    | Target        | req/s (median) | req/s (range) | p50   | p95   | p99   | Errors |
| ------- | ------------- | -------------- | ------------- | ----- | ----- | ----- | ------ |
| classic | `map/leaflet` | 179.7          | 155.1-185.3   | 23 ms | 41 ms | 48 ms | 0      |
| classic | `map/google`  | 180.3          | 163.1-183.9   | 23 ms | 41 ms | 45 ms | 0      |
| worker  | `map/leaflet` | 157.2          | 149.1-160.4   | 25 ms | 47 ms | 57 ms | 0      |
| worker  | `map/google`  | 151.4          | 148.2-151.8   | 27 ms | 47 ms | 55 ms | 0      |
