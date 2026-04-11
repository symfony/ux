---
name: symfony-ux-assets
description: >
    TypeScript/JS code style conventions for Symfony UX. Use when writing or editing JS/TS code.
---

# Symfony UX — JS/TS Style Rules

## When to Activate

User writes or edits TypeScript/JavaScript code in a Symfony UX package.

## Rules

- **Formatter**: oxfmt (`.oxfmtrc.json`)
- **Linter**: oxlint (`.oxlintrc.json`), default rules
- **Module system**: ESM (`"type": "module"`)
- **Imports**: named preferred, `type` keyword for type-only (`import type { ... }`)
- **Naming**: camelCase variables/functions, PascalCase classes/interfaces/types
- **Stimulus controllers**: extend `Controller` from `@hotwired/stimulus`, `static values = {}`, `declare readonly` for value props
- **Tests**: Vitest + `@testing-library/dom` + `@testing-library/jest-dom`. Playwright for browser tests.
- Assets must be compatible with Symfony AssetMapper and Webpack Encore.
- Dist files committed. Change TS → `pnpm run build` → commit `dist/`.
