# UX CSS benchmark

A Symfony 8.2 app that renders the same dashboard page twice, to compare the rendering cost of UX CSS (`symfony/ux-css`, its `css()` Twig function) with Tailwind CSS (the shadcn recipes of the UX Toolkit, which use `html_cva()` and the `tailwind_classes` filter of `tales-from-a-dev/twig-tailwind-extra`).

`/tailwind` uses the components of `templates/components/Tailwind/`, copied as is from `src/Toolkit/kits/shadcn/`. `/ux-css` uses their twins written with `css()`, in `templates/components/UxCss/`. Each page renders 364 components.

Only the HTML rendering is compared: neither page loads a stylesheet, and building the CSS is not measured.

## Run it

```shell
symfony php ../../.github/build-packages.php
symfony composer install
bin/install-frankenphp
bin/bench
```

`bin/install-frankenphp` downloads the static FrankenPHP binary (v1.13.5 by default, or the version given as argument) from the GitHub releases to `var/frankenphp/`, and checks its SHA-256.

`bin/bench` needs `ab` (ApacheBench), which ships with macOS. Its arguments are the number of rounds, the requests per page and round, and the concurrency: `bin/bench 5 2000 4` by default. It writes the results to `RESULTS.md`, and prints them.

## What `bin/bench` does

1. Dumps an authoritative class map of the autoloader and clears the `prod` cache (it restores the regular autoloader when it ends).
2. Serves the app with FrankenPHP in classic mode, then in worker mode, configured by `Caddyfile` and `php.ini`, which follows the [Symfony performance checklist](https://symfony.com/doc/current/performance.html): OPcache without timestamp checks, class preloading, realpath cache, and assertions off.
3. Checks that every page answers HTTP 200 and renders the same HTML on every request and in both modes.
4. Sends 500 warmup requests per page, then loads both pages round after round, in an order that alternates between rounds.
5. Writes `RESULTS.md`: requests per second (median and range over the rounds), latency percentiles, and the size and class count of each page.

## Known limitation

Button, Badge and Alert pick their variant with a spread, like `css({...base, ...variants[variant]})`. `css()` then computes their classes when the template renders, and the bundle writes no CSS for them, because `static_css` cannot list styles nested under several conditions.

This is what the upcoming `cva()` function is meant to solve. It does not change what the benchmark measures.
