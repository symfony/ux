# Results

Written by `bin/bench 5 2000 4` on 2026-11-11 09:45 UTC, from symfony/ux 940ffe1370b.

- FrankenPHP v1.13.5 with PHP 8.5.12, static binary, opcache and preloading on (see `php.ini`)
- Symfony 8.2.0-BETA1, Twig 3.30.0, tales-from-a-dev/twig-tailwind-extra 1.5.0, `prod` environment
- Apple M1 Pro, 10 cores, Darwin 27.2.0; `ab` runs on the same machine
- `ab -k -c 4`: 500 warmup requests per page, then 5 rounds of 2000 requests per page

## The pages

| Page                       | HTML     | `class` attributes | Classes | Unique classes |
| -------------------------- | -------- | ------------------ | ------- | -------------- |
| Tailwind CSS (`/tailwind`) | 164.5 KB | 505                | 6035    | 236            |
| UX CSS (`/ux-css`)         | 174.8 KB | 505                | 5689    | 227            |

## Requests per second

The range covers the 5 rounds; the latencies are the medians of the rounds.

| Mode    | Page         | req/s (median) | req/s (range) | p50  | p95  | p99   | Errors |
| ------- | ------------ | -------------- | ------------- | ---- | ---- | ----- | ------ |
| classic | Tailwind CSS | 469.3          | 467.8-477.6   | 8 ms | 9 ms | 10 ms | 0      |
| classic | UX CSS       | 496.6          | 488.3-497.5   | 8 ms | 9 ms | 9 ms  | 0      |
| worker  | Tailwind CSS | 536.6          | 531.8-537.1   | 7 ms | 8 ms | 9 ms  | 0      |
| worker  | UX CSS       | 544.9          | 542.4-553.6   | 7 ms | 8 ms | 8 ms  | 0      |

- classic mode: UX CSS serves +5.8% requests per second compared to Tailwind CSS; the rounds of a page spread over up to 2.1%.
- worker mode: UX CSS serves +1.5% requests per second compared to Tailwind CSS; the rounds of a page spread over up to 2.1%.
