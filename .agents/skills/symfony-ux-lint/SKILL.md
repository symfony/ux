---
name: symfony-ux-lint
description: >
    All build, test, lint, and format commands for Symfony UX. Single source of truth for running things.
---

# Symfony UX — Commands Reference

## When to Activate

User needs to build, test, lint, or format code.

## Build

```bash
pnpm run build                                    # all JS/TS packages
pnpm run build --filter @symfony/ux-autocomplete  # single package
cd src/<Package> && composer install               # PHP deps per package
```

## Test

### PHP (PHPUnit)

```bash
cd src/<Package>
php vendor/bin/phpunit                         # all tests
php vendor/bin/phpunit tests/Unit/SomeTest.php # single file
php vendor/bin/phpunit --filter testMethodName  # single method
```

### JS Unit (Vitest)

```bash
pnpm run test:unit                                # all packages (root)
cd src/<Package>/assets && pnpm run test:unit     # single package
pnpm exec vitest --run test/unit/some.test.ts     # single file
```

Note: `pnpm run test:unit` tests all peer dep combinations. Slow. No watch mode. Temporarily modifies `package.json` — don't commit those changes.

### JS Browser (Playwright)

```bash
pnpm run test:browser                             # all packages (root)
cd src/<Package>/assets && pnpm run test:browser  # single package
pnpm exec playwright test test/browser/some.test.ts # single file
pnpm run test:browser:ui                          # interactive/debug mode
```

Setup: see `apps/e2e/README.md`.

## Lint & Format

```bash
pnpm run fmt                      # format JS/TS/MD (oxfmt)
pnpm run fmt:check                # check only
pnpm run lint                     # lint JS/TS (oxlint)
pnpm run lint:fix                 # lint + auto-fix
php vendor/bin/php-cs-fixer fix   # PHP style
php vendor/bin/twig-cs-fixer lint # Twig templates
```

### RST docs

```bash
docker run --rm -it -e DOCS_DIR='/docs' -v ${PWD}:/docs oskarstark/doctor-rst -vvv
```

## Snapshots (UX Toolkit)

Update `EXAMPLES.md` first, then:

```bash
php vendor/bin/phpunit -d --update-snapshots
```
