---
name: symfony-ux-commit
description: >
    Pre-commit checklist for Symfony UX. Use when preparing to commit or create a PR.
---

# Symfony UX — Pre-Commit Checklist

## When to Activate

User is about to commit or create a PR.

## Checklist

1. **Tests pass** — run only affected packages (PHP and/or JS depending on changes)
2. **Assets built** — if TS/JS changed: `pnpm run build`, commit `dist/`
3. **Format clean** — `pnpm run fmt` + `php vendor/bin/php-cs-fixer fix`
4. **Lint clean** — `pnpm run lint:fix` + `php vendor/bin/twig-cs-fixer lint`
5. **Snapshots updated** — if Toolkit templates changed: update `EXAMPLES.md`, regenerate snapshots
6. **No temp files** — don't commit `package.json` changes from peer dep testing

See `symfony-ux-lint` skill for exact commands.
