---
name: symfony-ux-overview
description: >
    Symfony UX monorepo structure and setup. Use when asking about project layout, workspaces, or general orientation.
---

# Symfony UX — Monorepo Overview

## When to Activate

User asks about project structure, repo layout, workspaces, or "what is this repo".

## Structure

```
src/<Package>/
  src/               # PHP (PSR-4)
  tests/             # PHPUnit
  assets/
    src/             # TypeScript source
    dist/            # Built output (committed!)
    test/unit/       # Vitest
    test/browser/    # Playwright
    package.json
  config/            # Symfony DI definitions
  composer.json
apps/
  demo-native/       # Manual testing for UX Native
  e2e/               # Playwright browser tests
  encore/            # Webpack Encore integration tests
```

## Key Facts

- Monorepo: PHP bundles + JS/TS Stimulus controllers.
- Package manager: **pnpm** v10 (via Corepack). Node >= 22. PHP >= 8.1.
- pnpm workspaces: `src/*/assets` and `src/*/src/Bridge/*/assets`.
- Dist files are committed. Change TS → `pnpm run build` → commit `dist/`.
- Peer dep matrix: JS unit tests run multiple peer dep versions via `bin/unit_test_package.sh`.
- PHPStan: only `src/Turbo`.
- Snapshot tests (Toolkit): update `EXAMPLES.md` first, then `php vendor/bin/phpunit -d --update-snapshots`.
- Link utility: `php link /path/to/project` symlinks UX packages into existing project.

## Setup

Requires: PHP >= 8.4, Composer, Node >= 22.18, Corepack, pnpm >= 10.16.1.

```bash
composer install
corepack enable && pnpm install
```
