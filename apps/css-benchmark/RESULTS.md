# Results

Written by `bin/bench 5 2000 4` on 2026-11-14 21:48 UTC, from symfony/ux 321ef6c0017.

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
| classic | Tailwind CSS | 487.2          | 478.8-494.7   | 8 ms | 9 ms | 10 ms | 0      |
| classic | UX CSS       | 509.2          | 500.5-513.3   | 8 ms | 9 ms | 9 ms  | 0      |
| worker  | Tailwind CSS | 544.5          | 537.8-547.7   | 7 ms | 8 ms | 8 ms  | 0      |
| worker  | UX CSS       | 571.9          | 568.4-574.9   | 7 ms | 8 ms | 8 ms  | 0      |

- classic mode: UX CSS serves +4.5% requests per second compared to Tailwind CSS; the rounds of a page spread over up to 3.3%.
- worker mode: UX CSS serves +5.0% requests per second compared to Tailwind CSS; the rounds of a page spread over up to 1.8%.
