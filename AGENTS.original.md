# AGENTS.md — Symfony UX Monorepo

## Project Overview

Symfony UX is a monorepo of PHP (Symfony bundles) + TypeScript/JS (Stimulus controllers) packages.
Each package lives under `src/<Package>/` with PHP code in `src/` and JS assets in `assets/`.
Package manager: **pnpm** (v10, via Corepack). Node 22. PHP >= 8.1.

## Build Commands

```bash
# JS/TS — from repo root
pnpm install
pnpm run build            # build all packages (tsdown)

# JS/TS — single package
pnpm run build --filter @symfony/ux-autocomplete

# PHP — per-package
cd src/Autocomplete && composer install
```

## Test Commands

### PHP (PHPUnit via Symfony PHPUnit Bridge)

```bash
# All tests for a package
cd src/Autocomplete
php vendor/bin/simple-phpunit

# Single test file
php vendor/bin/simple-phpunit tests/Unit/SomeTest.php

# Single test method
php vendor/bin/simple-phpunit --filter testMethodName
```

### JS Unit Tests (Vitest)

```bash
# All packages from root
pnpm run test:unit

# Single package
cd src/Autocomplete/assets
pnpm run test:unit

# Single test file
pnpm exec vitest --run test/unit/some.test.ts
```

### JS Browser Tests (Playwright)

```bash
# All packages from root
pnpm run test:browser

# Single package
cd src/Autocomplete/assets
pnpm run test:browser

# Single test file
pnpm exec playwright test test/browser/some.test.ts

# Interactive UI mode
pnpm run test:browser:ui
```

## Lint & Format Commands

```bash
# JS/TS lint (oxlint)
pnpm run lint
pnpm run lint:fix

# JS/TS/MD format (oxfmt)
pnpm run fmt
pnpm run fmt:check

# PHP code style (PHP-CS-Fixer, @Symfony ruleset)
php vendor/bin/php-cs-fixer fix

# Twig templates
php vendor/bin/twig-cs-fixer lint
```

## PHP Code Style

- **Ruleset**: `@Symfony` + `@Symfony:risky` via PHP-CS-Fixer (`.php-cs-fixer.dist.php`)
- **`declare(strict_types=1)`**: not enforced globally, but follow existing file conventions
- **Namespaces**: PSR-4, e.g. `Symfony\UX\Autocomplete\...`
- **Classes**: prefer `final` classes. Use PascalCase. No `readonly` classes.
- **Methods**: camelCase, always typed parameters and return types
- **Properties**: typed, use constructor promotion and `readonly` where appropriate
- **Imports**: one `use` per line, grouped (PHP classes, then traits, then interfaces). No aliasing unless conflicts.
- **File header**: every PHP file must have the Symfony license header comment (auto-fixed by CS fixer):
    ```php
    /*
     * This file is part of the Symfony package.
     *
     * (c) Fabien Potencier <fabien@symfony.com>
     *
     * For the full copyright and license information, please view the LICENSE
     * file that was distributed with this source code.
     */
    ```
- **Error handling**: throw specific exceptions (InvalidArgumentException, LogicException, RuntimeException). No generic `\Exception`.
- **Doc comments**: use `@author` on classes. PHPDoc only when types can't express the contract (generics, union details). Don't duplicate type info already in signatures.

## TypeScript/JS Code Style

- **Formatter**: oxfmt (`.oxfmtrc.json`)
- **Linter**: oxlint (`.oxlintrc.json`), default rules
- **Module system**: ESM (`"type": "module"`)
- **Imports**: named imports preferred, `type` keyword for type-only imports (`import type { ... }`)
- **Naming**: camelCase for variables/functions, PascalCase for classes/interfaces/types
- **Stimulus controllers**: extend `Controller` from `@hotwired/stimulus`, use `static values = {}` pattern, `declare readonly` for value properties
- **Tests**: Vitest with `@testing-library/dom` + `@testing-library/jest-dom` matchers. Playwright for browser tests.

## Repository Structure

```
src/
  <Package>/
    .github/             # CI workflows, PR template, for subtree split
    .gitignore
    .gitattributes
    src/                 # PHP source (PSR-4)
    tests/               # PHPUnit tests
    assets/              # Frontend assets
      src/               # TypeScript source
      dist/              # Built output (committed!)
      test/              # JS tests (unit/ and browser/)
      package.json
      vitest.config.mjs
      playwright.config.ts
    config/              # Symfony DI service definitions
    composer.json
    phpunit.xml.dist
apps/
  demo-native/           # Symfony app for manual testing and demos of Symfony UX Native
  e2e/                   # Symfony app for Playwright browser tests
  encore/                # Symfony app for testing Webpack Encore and `npm install` integration
```

## Important Notes

- **Dist files are committed**: after changing TS source, run `pnpm run build` and commit the `dist/` output.
- **pnpm workspaces**: packages are `src/*/assets` and `src/*/src/Bridge/*/assets`.
- **Peer dependency matrix**: JS unit tests may run against multiple versions of peer deps (handled by `bin/unit_test_package.sh`).
- **PHPStan**: currently only configured for `src/Turbo` (`phpstan.dist.neon`).
- **Snapshot tests**: some packages (Toolkit) use PHPUnit snapshots — update with `php vendor/bin/simple-phpunit -d --update-snapshots`.

## Before committing

1. Ensure to have written tests for any new features or bug fixes, and that all tests pass locally, if it makes sense to do so (e.g. for a pure JS change, running PHP tests is not required).
2. Run `oxfmt` and `oxlint` to ensure non-PHP code style and linting are clean,
3. Run `php-cs-fixer` to ensure PHP code style is clean,
4. Run `twig-cs-fixer` to ensure Twig templates are properly formatted,
5. Run all tests for the affected package(s) (PHPUnit, Vitest, Playwright) to ensure no regressions:
    1. Be careful with JS unit-tests, the `package.json` can be modified to run tests against multiple versions of peer dependencies,
       so make sure to not accidentally commit temporary changes to `package.json`
