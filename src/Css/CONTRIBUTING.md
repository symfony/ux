# Contributing

The Symfony UX CSS package is a PHP port of Panda CSS's `css()` function for Twig. Its correctness is checked against Panda CSS itself, so the setup is a bit more involved than for other packages. Please follow the instructions below to set up your development environment.

## Setting up the development environment

First, ensure you have followed the [Symfony UX's Contribution Guide](https://github.com/symfony/ux/blob/3.x/CONTRIBUTING.md) to set up your fork of the main repository, install dependencies, etc.

Then, install the UX CSS package dependencies:

```shell
# src/Css
composer install
```

You can then run the tests with:

```shell
# src/Css
php vendor/bin/phpunit
```

## Golden tests

The package must produce exactly the same class names and CSS as Panda CSS. Rather than rewriting Panda's tests by hand, we run Panda's own test suite with a small instrumentation that records every `css()` call: the input, the Panda config it ran with, and the output Panda produced. These recordings are the "golden" files, reference outputs coming from the trusted implementation. The PHP engine replays each recorded input and must produce the same string, byte for byte. Panda is pinned to one commit, stored in `tests/Fixtures/Panda/PANDA_COMMIT`.

## Where things live

- `tests/Fixtures/Panda/cases/*.json`: one file per Panda test file, one entry per `css()` call, with its inputs, expected output, config name, and, for tests that use their own config, a patch on top of the base config.
- `tests/Fixtures/Panda/configs/*.json`: the Panda configs the cases run with (the test fixture config and the codegen scenarios).
- `tests/Fixtures/Panda/utilities.json`: what Panda's `utility.transform()` returns for every utility of the fixture config, with about ten values each. Replayed by `tests/Golden/PandaUtilitiesTest.php`, which has no ratchet: every recorded value must match, except Panda's text styles and layer styles, which are skipped as out of v1.
- `tests/Fixtures/Panda/tokens.json`: the views of Panda's token dictionary for the fixture config (CSS variable of each token, raw values, values by category, color palettes, CSS variables by condition). Replayed by `tests/Golden/PandaTokensTest.php`, which has no ratchet: every value must match.
- `tests/Fixtures/Panda/expectations.php`: the ratchet, see below.
- `tests/Golden/PandaGoldenTest.php`: replays the cases.
- `tools/panda-golden/`: the recorder script and the hooks it injects into Panda.

## How a case can end

Each case replayed by `PandaGoldenTest` ends in one of three ways:

- it passes: the output is identical to Panda's;
- it is skipped, with a reason: either it was recorded as unsupported (the Panda test also used recipes, patterns, JSX, config hooks, etc.), or it is marked "Out of v1" for a Panda feature the package does not support on purpose, for example Panda's template literal syntax or hashed class names;
- it is incomplete: not ported yet, or the output differs from Panda's. This is only allowed for cases that never passed before.

Some cases record the CSS of Panda's tokens layer (`generate-token.test.ts`). Those are compared after removing the whitespace and the last semicolon before each closing brace, because Panda re-parses that CSS several times and leaves random indentation there. Everything else must be identical.

`tests/Fixtures/Panda/expectations.php` lists every case id that currently passes, and this list acts as a ratchet: once a case is listed, it must keep passing, or the test fails. After making more cases pass, update the list with:

```shell
# src/Css
php tests/Golden/update-expectations.php
```

then commit the result. The script refuses to drop a case that used to pass; pass `--allow-regressions` to force it anyway. `expectations.php` also has a `skipped` map (case id to reason) to skip a case by hand.

## Regenerating the golden files

From the repository root, run:

```shell
# repository root
node src/Css/tools/panda-golden/record.mjs
```

This needs Node 22, pnpm and git. On the first run, it clones Panda into `~/.cache/symfony-ux-css/panda` (override with the `PANDA_DIR` environment variable) and installs its dependencies. After that, a run takes about a minute.

The script rewrites `cases/`, `configs/`, `utilities.json`, `tokens.json`, `PANDA_COMMIT` and `resources/panda-preset.json`, and it lists any case file that no longer matches a Panda test file: remove those with `git rm`. Unlike the other files, `resources/panda-preset.json` is not a test fixture: it is the default Panda preset shipped with the package (the conditions and utilities of `preset-base`, the breakpoints and container sizes of `preset-panda`, no tokens), and the bundle uses it at runtime. Then, from `src/Css`, run `php tests/Golden/update-expectations.php`, run the tests, and commit everything.

## Moving to a newer Panda

Change `PANDA_COMMIT` in `tools/panda-golden/record.mjs`, then run the recorder again. If Panda's code changed where the recorder injects its hooks, the script stops and prints the snippet it could not find: adapt the patch in `record.mjs` accordingly. Then update the expectations as above, and look at every case that stopped passing.

## Measuring performance

The benchmarks live in `tests/Benchmark/` and run with [PHPBench](https://phpbench.readthedocs.io/). They measure:

- `css()` at runtime, for a flat hash, a hash with conditions, a responsive hash, and the dynamic part of a hash;
- loading the class name tables from the container;
- compiling a template with 100 `css()` calls, next to the same template without `css()`;
- generating the CSS of 1000 hashes;
- a dev request on a project of 200 templates, with no change and after one template changed;
- the cache warmup of the same project.

Run them from `src/Css`:

```shell
# src/Css
vendor/bin/phpbench run --report=css
```

Each benchmark has a budget, about ten times what it takes today. The run fails if a benchmark goes over its budget. A budget only catches a big regression, not a small one. To measure the effect of a change, record a reference before the change, then compare after it:

```shell
# src/Css, before the change
vendor/bin/phpbench run --report=css --tag=before

# src/Css, after the change
vendor/bin/phpbench run --report=css --ref=before
```

The report then shows, for each benchmark, how much it moved. Run both commands on the same machine, under the same conditions.

`phpbench.json` turns on opcache as in production, including for files written just before a benchmark runs, such as the container, and turns off Xdebug.
