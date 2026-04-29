# AGENTS.md — Symfony UX Monorepo

## Project Overview

Monorepo of PHP (Symfony bundles) + TypeScript/JS (Stimulus controllers) packages.
Each package: `src/<Package>/`, PHP in `src/`, JS in `assets/`.
Package manager: **pnpm** (v10, via Corepack). Node 22. PHP >= 8.4.

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

### PHP (PHPUnit)

```bash
# All tests for a package
cd src/Autocomplete
php vendor/bin/phpunit

# Single test file
php vendor/bin/phpunit tests/Unit/SomeTest.php

# Single test method
php vendor/bin/phpunit --filter testMethodName
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
- **`declare(strict_types=1)`**: not enforced globally; follow file conventions
- **Namespaces**: PSR-4, e.g. `Symfony\UX\Autocomplete\...`
- **Classes**: prefer `final`. PascalCase. No `readonly` classes.
- **Methods**: camelCase, typed params + return types
- **Properties**: typed, constructor promotion + `readonly` where appropriate
- **Imports**: one `use` per line, grouped (classes, traits, interfaces). No aliasing unless conflicts.
- **File header**: every PHP file needs Symfony license header (auto-fixed by CS fixer):
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
- **Doc comments**: `@author` on classes. PHPDoc only when types can't express contract (generics, union details). No duplicate type info.

## TypeScript/JS Code Style

- **Formatter**: oxfmt (`.oxfmtrc.json`)
- **Linter**: oxlint (`.oxlintrc.json`), default rules
- **Module system**: ESM (`"type": "module"`)
- **Imports**: named imports preferred, `type` keyword for type-only (`import type { ... }`)
- **Naming**: camelCase vars/funcs, PascalCase classes/interfaces/types
- **Stimulus controllers**: extend `Controller` from `@hotwired/stimulus`, `static values = {}` pattern, `declare readonly` for value props
- **Tests**: Vitest + `@testing-library/dom` + `@testing-library/jest-dom`. Playwright for browser.

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

- **Dist files committed**: after TS source change, run `pnpm run build` + commit `dist/`.
- **pnpm workspaces**: `src/*/assets` and `src/*/src/Bridge/*/assets`.
- **Peer dependency matrix**: JS unit tests may run against multiple peer dep versions (via `bin/unit_test_package.sh`).
- **PHPStan**: only configured for `src/Turbo` (`phpstan.dist.neon`).
- **Snapshot tests**: Toolkit uses PHPUnit snapshots — update with `php vendor/bin/simple-phpunit -d --update-snapshots`.

## Before committing

1. Write tests for new features/bug fixes; all tests pass locally, when applicable (pure JS change → skip PHP tests).
2. Run `oxfmt` + `oxlint` (non-PHP code style/lint clean),
3. Run `php-cs-fixer` (PHP code style clean),
4. Run `twig-cs-fixer` (Twig formatted),
5. Run all tests for affected package(s) (PHPUnit, Vitest, Playwright):
    1. JS unit-tests: `package.json` can be modified to test against multiple peer dep versions — don't commit temp `package.json` changes.
