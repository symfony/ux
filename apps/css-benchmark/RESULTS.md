# Results

Written by `bin/bench 5 2000 4` on 2026-11-10 07:30 UTC, from symfony/ux 004907882a0.

- FrankenPHP v1.13.5 with PHP 8.5.12, static binary, opcache and preloading on (see `php.ini`)
- Symfony 8.2.0-BETA1, Twig 3.30.0, tales-from-a-dev/twig-tailwind-extra 1.5.0, `prod` environment
- Apple M1 Pro, 10 cores, Darwin 27.2.0; `ab` runs on the same machine
- `ab -k -c 4`: 500 warmup requests per page, then 5 rounds of 2000 requests per page

## The pages

| Page                       | HTML     | `class` attributes | Classes | Unique classes |
| -------------------------- | -------- | ------------------ | ------- | -------------- |
| Tailwind CSS (`/tailwind`) | 164.5 KB | 505                | 6035    | 236            |
| UX CSS (`/ux-css`)         | 174.6 KB | 505                | 5681    | 227            |

## Requests per second

The range covers the 5 rounds; the latencies are the medians of the rounds.

| Mode    | Page         | req/s (median) | req/s (range) | p50  | p95  | p99   | Errors |
| ------- | ------------ | -------------- | ------------- | ---- | ---- | ----- | ------ |
| classic | Tailwind CSS | 467.2          | 460.4-477.9   | 9 ms | 9 ms | 10 ms | 0      |
| classic | UX CSS       | 465.2          | 461.8-468.8   | 9 ms | 9 ms | 10 ms | 0      |
| worker  | Tailwind CSS | 535.0          | 529.2-536.0   | 7 ms | 8 ms | 8 ms  | 0      |
| worker  | UX CSS       | 546.1          | 543.2-553.2   | 7 ms | 8 ms | 8 ms  | 0      |

- classic mode: UX CSS serves -0.4% requests per second compared to Tailwind CSS; the rounds of a page spread over up to 3.7%.
- worker mode: UX CSS serves +2.1% requests per second compared to Tailwind CSS; the rounds of a page spread over up to 1.8%.
