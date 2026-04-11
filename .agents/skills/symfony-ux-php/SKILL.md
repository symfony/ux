---
name: symfony-ux-php
description: >
    PHP code style conventions for Symfony UX. Use when writing or editing PHP code.
---

# Symfony UX — PHP Style Rules

## When to Activate

User writes or edits PHP code in a Symfony UX package.

## Rules

- **CS-Fixer ruleset**: `@Symfony` + `@Symfony:risky` (`.php-cs-fixer.dist.php`)
- **`declare(strict_types=1)`**: follow existing file conventions
- **Namespaces**: PSR-4 → `Symfony\UX\<Package>\...`
- **Classes**: prefer `final`, PascalCase, no `readonly` classes
- **Methods**: camelCase, typed params + return types
- **Properties**: typed, constructor promotion + `readonly` where fit
- **Imports**: one `use` per line, grouped (classes → traits → interfaces), no alias unless conflict
- **Exceptions**: specific types only (`InvalidArgumentException`, `LogicException`, `RuntimeException`). No `\Exception`.
- **PHPDoc**: `@author` on classes. Only when types can't express contract. Don't duplicate signatures.
- **File header** (auto-fixed by CS-Fixer):
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
- **Docs**: `.rst` in `docs/` per package. Follow [Symfony doc guidelines](https://symfony.com/doc/current/contributing/documentation/index.html).
- Follow [Symfony BC Promise](https://symfony.com/doc/current/contributing/code/bc.html).
