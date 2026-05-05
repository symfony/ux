---
name: symfony-ux-toolkit-kit
description: >
    Generate, modify, or review Symfony UX Toolkit kit recipes (Shadcn, Flowbite, future kits).
    Enforces conventions for manifest, Twig component docblocks, sub-components, asChild
    `<recipe>_<role>_attrs` pattern, outer-scope propagation, Stimulus controllers, examples,
    snapshots, and PR hygiene. Use when adding/editing files under `src/Toolkit/kits/` or
    reviewing PRs touching the Toolkit.
---

# Symfony UX Toolkit — Kit Recipe Skill

Author + review recipes for UX Toolkit. Recipes = unit shipped to end-users (Twig components + optional Stimulus controllers + examples).

## When to Activate

- User says "add recipe", "new kit recipe", "Toolkit component", "port shadcn X", or similar.
- Any file change under `src/Toolkit/kits/<kit>/<recipe>/`.
- Reviewing PR titled `[Toolkit][...]`.

---

## Core Rules

1. **One PR per recipe.** Never batch multiple recipes single PR. PR title:
   `[Toolkit][<Kit>] Add <recipe> recipe` or `[Toolkit][<Kit>] Align <recipe> with <upstream> reference`.
2. **Target `3.x`.** CHANGELOG entry under active `3.x` section in `src/Toolkit/CHANGELOG.md`.
3. **Visual + behavioral parity** with upstream reference (Shadcn UI / Flowbite). Verify manually; attach screenshot/video to PR body for animated/interactive components.
4. **Reuse all upstream examples.** No subset. Read both component source **and** every example file (see [Shadcn UI](#shadcn-ui) / [Flowbite v4](#flowbite-v4)).
5. **Companion PR on `symfony/ux.symfony.com`** for visual preview/docs. Link in recipe PR body.
6. **Regenerate snapshots** after every recipe change + commit. CI + reviewers reject stale snapshots.
7. **Use GitHub PR template** (Bug fix / Feature / License: MIT / Issues: Part of #3233). Fabbot fails otherwise.
8. **Prefer Stimulus controller** over native browser features (e.g. `<details>`) when parity needs animations, ARIA sync, coordinated state. Native fine only when matches upstream UX exactly.

---

## Recipe Directory Layout

```
src/Toolkit/kits/<kit>/<recipe>/
├── manifest.json
├── examples/
│   ├── Usage.html.twig                    # mandatory, minimal API showcase
│   ├── Demo.html.twig                     # mandatory, rich preview (used on ux.symfony.com)
│   └── <Variant Name>.html.twig           # one per upstream example, Title Case with spaces
├── templates/components/
│   ├── <Component>.html.twig              # root component
│   └── <Component>/<SubName>.html.twig    # e.g. Trigger, Close, Header, Item, Content
└── assets/controllers/                    # optional, only if interactive behavior is needed
    └── <recipe>_controller.js
```

Sub-component file path `Component/SubName.html.twig` consumed as `<twig:Component:SubName>`.

---

## Shadcn UI

Always emit `data-slot="<recipe-name>"` on root + `data-slot="<recipe-name>-<sub>"` on every sub-component root. Shadcn-specific convention driven by its CSS selectors.

### Upstream sources

Read **both** files per recipe: component source carries canonical classes + `data-*` surface; examples show usage patterns.

| File | Purpose |
| --- | --- |
| `apps/v4/styles/radix-nova/ui/<recipe>.tsx` | **Component source** — canonical Tailwind classes, sub-component structure, `data-slot`/`data-state` surface |
| `apps/v4/examples/radix/<recipe>-*.tsx` | **Usage examples** — one file per variant, drives examples list |

Enumerate every example file for recipe:

```bash
gh api "repos/shadcn-ui/ui/git/trees/main?recursive=1" --jq '.tree[].path' \
  | grep "apps/v4/examples/radix/<recipe>"
```

Fetch each:
```
https://raw.githubusercontent.com/shadcn-ui/ui/refs/heads/main/apps/v4/examples/radix/<example>.tsx
```

---

## Flowbite v4

Kit identifier: `flowbite-4`.

### Upstream sources

| Source | Purpose |
| --- | --- |
| `https://flowbite.com/docs/components/<recipe>/` | **Reference page** — canonical markup, variants, accessibility notes |
| `https://github.com/themesberg/flowbite/blob/main/src/components/<recipe>/index.ts` | **JS source** — behavior, state, options (when Stimulus controller needed) |

Flowbite docs page = primary source: ships copy-pasteable HTML with Tailwind classes + lists every variant. Read full page before writing any template.

---

## Local Visual Testing

`ux` and `ux.symfony.com` **must be on matching branches**; mismatch causes assetmap failures:

> The asset "./vendor/symfony/ux-toolkit/kits/<kit>/<recipe>/assets/controllers/<recipe>_controller.js" cannot be found in any asset map paths.

```bash
cd /path/to/ux             && git checkout feat/toolkit-<kit>-<recipe>
cd /path/to/ux.symfony.com && git checkout docs/<kit>-<recipe>
# In ux.symfony.com:
php ../link
symfony php bin/console tailwind:build
symfony serve -d
```

---

## Companion PR on ux.symfony.com

Every recipe PR needs companion PR on `symfony/ux.symfony.com`. Contents:

- `templates/toolkit/docs/<kit>/<recipe>.md.twig` extending `_base_component.md.twig`
- One `{{ toolkit_code_example(...) }}` per example file shipped — missing entries cause silent rendering failures
- If JS: register Stimulus controller in `assets/toolkit-<kit>.js` + `importmap.php`
- Run `symfony php bin/console tailwind:build` + commit CSS output
- Attach screenshot/video of every interactive state

Link companion PR URL in recipe PR body before requesting review.

---

## `manifest.json`

### Kit-level (`src/Toolkit/kits/<kit>/manifest.json`)

```json
{
    "$schema": "../../schema-kit-v1.json",
    "name": "<Display Name>",
    "description": "...",
    "license": "MIT",
    "homepage": "https://ux.symfony.com/toolkit/kits/<kit>"
}
```

### Recipe-level (`src/Toolkit/kits/<kit>/<recipe>/manifest.json`)

```json
{
    "$schema": "../../../schema-kit-recipe-v1.json",
    "type": "component",
    "name": "<Human Name>",
    "description": "<short, ends with a period>",
    "copy-files": {
        "assets/": "assets/",
        "templates/": "templates/"
    },
    "dependencies": {
        "composer": [
            "twig/extra-bundle",
            "twig/html-extra:^3.12.0",
            "tales-from-a-dev/twig-tailwind-extra:^1.0.0"
        ],
        "recipe": ["<other-recipe>"]
    }
}
```

Rules:
- Drop `assets/` from `copy-files` if no Stimulus controller.
- Add `"symfony/ux-icons"` to `composer` whenever templates use `<twig:ux:icon>`.
- Bump `twig/html-extra` constraint when using newer filters (e.g. `^3.24.0` for current `html_attr_type`).
- Declare `dependencies.recipe` for inter-recipe deps (e.g. `toggle-group` depends on `toggle`).

---

## Twig Component Patterns

### 1. Header docblock (mandatory)

Every component starts with one `{# @prop ... #}` per declared prop + one `{# @block content ... #}`:

```twig
{# @prop id string Unique identifier used to generate internal Dialog IDs #}
{# @prop open boolean Whether the dialog is open on initial render. Defaults to `false` #}
{# @block content The dialog structure, typically includes `Dialog:Trigger` and `Dialog:Content` #}
{%- props id, open = false -%}
```

- Type uses Twig/PHP-flavored union syntax: `'default'|'secondary'|...`, `string|array<string>|null`, `boolean`, `number`.
- "Defaults to \`<value>\`" when default exists.
- Reference sub-components by Twig tag name (`\`Dialog:Trigger\``).

### 2. Root element

```twig
<div
    class="{{ ('<base classes> ' ~ attributes.render('class'))|tailwind_merge }}"
    {{ attributes.defaults({
        'data-slot': '<recipe-name>',
        'data-controller': '<recipe>',
        'data-<recipe>-<key>-value': value,
        'aria-labelledby': _<recipe>_title_id,
    }) }}
>
    {%- block content %}{% endblock -%}
</div>
```

- Always use `attributes.defaults({...})` (NOT raw `{{ attributes }}`). Consumers must override.
- Class merging **mandatory**: `('<base> ' ~ attributes.render('class'))|tailwind_merge`.

### 3. Variant systems with `html_cva`

```twig
{%- set style = html_cva(
    base: '<base classes>',
    variants: {
        variant: { default: '...', outline: '...' },
        size: { default: '...', sm: '...', lg: '...' },
    },
) -%}
<button class="{{ style.apply({variant: variant, size: size}, attributes.render('class'))|tailwind_merge }}">
```

### 4. Parent → descendant context propagation

**Preferred: `provide()` / `inject()`** (needs `symfony/ux-twig-component:^3.1`). Parent publishes values, any descendant at any depth reads them. Works for self-closing children, crosses intermediate components without forwarding, replaces brittle outer-scope pattern.

```twig
{# parent — InputOtp.html.twig #}
{%- props maxLength = 6 -%}
{%- do provide('inputOtp.maxLength', maxLength) -%}
{%- do provide('inputOtp.id', 'input-otp-' ~ id) -%}
<div ...>{%- block content %}{% endblock -%}</div>
```

```twig
{# descendant — InputOtp/Slot.html.twig (works even self-closing) #}
{%- set _inputOtp_maxLength = inject('inputOtp.maxLength', 6) -%}
{%- set _inputOtp_id = inject('inputOtp.id') -%}
```

Conventions:
- Key format: `'<camelCaseRecipe>.<key>'` (e.g. `'inputOtp.maxLength'`, `'tabs.active'`, `'toggleGroup.variant'`). Prefix avoids collisions across recipes.
- **Local variable name for injected values: `_<camelCaseRecipe>_<key>`** (e.g. `_tabs_defaultValue`, `_toggleGroup_variant`). The `_` prefix + recipe name prevents collision with the child's own props or Twig globals.
- Always pass fallback to `inject()` when child can render standalone.
- Place `provide()` at top of parent template, **before** `{% block content %}` — descendants only see values published before their render.
- Keys for ID-driven a11y wiring: derive `<recipe>.id`, `<recipe>.titleId`, `<recipe>.descriptionId`, `<recipe>.contentId`, `<recipe>.triggerId` from parent's `id` prop.
- Values flow top-down only; siblings never share state; provides dropped once parent finishes rendering.

**Legacy: outer-scope `_<recipe>_<key>` variables.** Older recipes use `{%- set _dialog_title_id = ... -%}` read by children with `??` fallback. Still works for body-form children but **breaks for self-closing components** (`<twig:X:Item .../>` compiles without outer context). Migrate to `provide()`/`inject()` when touching such recipes.

### 5. The `<recipe>_<role>_attrs` (asChild) pattern

Sub-templates like `Trigger.html.twig`, `Close.html.twig`, `Cancel.html.twig` MUST NOT wrap user's element in own `<button>`. Instead expose attrs bag consumer spreads onto own element:

```twig
{# templates/components/Dialog/Trigger.html.twig #}
{# @block content The trigger element (e.g., a `Button`) that opens the dialog when clicked #}
{%- set dialog_trigger_attrs = {
    'data-action': 'click->dialog#open'|html_attr_type('sst'),
    'data-dialog-target': 'trigger',
    'aria-haspopup': 'dialog',
} -%}
{%- block content %}{% endblock -%}
```

```twig
{# example consumer #}
<twig:Dialog:Trigger>
    <twig:Button {{ ...dialog_trigger_attrs }}>Open</twig:Button>
</twig:Dialog:Trigger>
```

Rules:
- Variable name: **`<snake_case_recipe>_<role>_attrs`** — `dialog_trigger_attrs`, `dialog_close_attrs`, `tooltip_trigger_attrs`, `collapsible_trigger_attrs`, `alert_dialog_trigger_attrs`.
- Apply `|html_attr_type('sst')` to `data-action`. `'sst'` = Stimulus Shorthand Token — marks value appendable, so consumer spreading `{{ ...dialog_trigger_attrs }}` alongside own `data-action` gets both merged rather than first overwritten.
- Template body = `{%- block content %}{% endblock -%}` only — no wrapping element, otherwise variable not visible to outer scope.
- Variant (when wrapping known component acceptable, e.g. `AlertDialog:Action`):

```twig
{# @prop variant 'default'|'destructive' ... #}
{%- props variant = 'default' -%}
<twig:Button variant="{{ variant }}" {{ ...attributes }}>
    {{- block(outerBlocks.content) -}}
</twig:Button>
```

### 6. Collapse/expand animation — `grid-template-rows`, never `hidden`

`hidden` (display:none) causes layout jumps. Use `grid-template-rows: 0fr` + `overflow:hidden` for smooth transitions:

```twig
<div class="grid overflow-hidden transition-[grid-template-rows] duration-300 ease-out"
     style="grid-template-rows: {{ open ? '1fr' : '0fr' }};">
    <div class="min-h-0 min-w-0 overflow-hidden">
        {%- block content %}{% endblock -%}
    </div>
</div>
```

Stimulus controller toggles `style.gridTemplateRows` between `'1fr'` and `'0fr'` on open/close.

### 7. ARIA & data-state surfaces

Always emit (when applicable):
- `role`, `aria-haspopup`, `aria-expanded`, `aria-controls`, `aria-labelledby`, `aria-describedby`, `aria-disabled`, `aria-pressed`, `aria-hidden`.
- `data-state="open|closed|active|inactive"`, `data-orientation="vertical|horizontal"`, `data-variant`, `data-size`, `data-disabled`, `data-open`, `data-closed`.
- IDs deterministic + shared between trigger/content via parent's `id` prop (e.g. `aria-controls={{ _accordion_item_content_id }}`).

---

## Stimulus Controller Conventions

`assets/controllers/<recipe>_controller.js`:

```js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger', 'content'];
    static values = { open: Boolean };

    connect() {
        if (this.openValue) this.open();
    }

    open() { /* ...sync ARIA after transitions... */ }
    close() { /* ... */ }
}
```

- ESM, default export, `@hotwired/stimulus`.
- Sync ARIA from JS on state changes (`aria-expanded`, `data-state`).
- Respect transitions: `if (el.getAnimations().length > 0) el.addEventListener('transitionend', ..., { once: true });`.
- Naming: `<recipe>_controller.js`, controller identifier `<recipe>` (kebab-case in Twig).
- **Keyboard actions** — use Stimulus descriptor syntax in Twig, not raw JS `keydown` listeners:
  ```twig
  data-action="keydown.enter->{{ recipe }}#toggle keydown.space->{{ recipe }}#toggle"
  ```
  Pipe through `|html_attr_type('sst')` when exposing via `<recipe>_<role>_attrs` so consumers can append own actions.
- **Hover/focus-triggered components** — never use `group-hover` + `group-focus-within` + `tabindex=0`; use Stimulus controller with `openDelay`/`closeDelay` values instead (see anti-patterns).
- **Nested open-state** — never use `in-data-[state=open]:visible` on nested components; use named Tailwind groups (`group/<recipe>-menu`, `group/<recipe>-sub`) instead (see anti-patterns).

---

## Examples Conventions

- File names: **Title Case with spaces**, e.g. `Custom close button.html.twig`, `With Icon.html.twig`, `Different sizes.html.twig`, `RTL.html.twig`, `File Tree.html.twig`.
- **Mandatory**: `Usage.html.twig` (minimal call surface) + `Demo.html.twig` (rich showcase used as kit preview).
- One example per upstream variant. Match upstream copy/structure where possible.
- When upstream uses cross-cutting JS (e.g. shadcn's `language-selector`), replicate intent without inventing new infrastructure (e.g. stack two independent components for RTL+LTR side-by-side, see `collapsible/RTL`).

---

## Tests & Snapshots

```bash
cd src/Toolkit

# When examples were renamed/removed, blow them away first
rm -fr tests/Functional/__snapshots__/*<recipe>*

# Regenerate
php vendor/bin/simple-phpunit -d --update-snapshots

# Re-run normally to confirm green
php vendor/bin/simple-phpunit

git add tests/Functional/__snapshots__
```

Reviewers explicitly check snapshots regenerated (`#3488`).

**Orphan snapshots:** when recipe rewritten (e.g. `<details>` → Stimulus), old snapshot files with old naming scheme (e.g. `Demo.html__1.html` without `.twig` suffix) never regenerated + silently persist. After regenerating, inspect `git status` for leftover files + `git rm` them.

**After rebase on `3.x`:** snapshot formatter may have evolved upstream. Re-run `--update-snapshots` once more after final rebase to avoid "diff in snapshots" CI failures.

---

## Authoring Workflow

1. Locate upstream reference (see [Shadcn UI](#shadcn-ui) / [Flowbite v4](#flowbite-v4)) — list every example variant before writing any code
2. Scaffold recipe directory + `manifest.json`
3. Root component, sub-components (with `<recipe>_<role>_attrs`), Stimulus controller if needed
4. Examples: `Usage.html.twig`, `Demo.html.twig`, then every upstream variant
5. Snapshots — regenerate, inspect HTML diff, commit
6. Lint/format, CHANGELOG entry, open PR + companion PR

---

## PR / Review Checklist

- [ ] Single recipe per PR
- [ ] Targets `3.x`
- [ ] PR template filled (Bug/Feature, License: MIT, Issues: Part of #3233)
- [ ] CHANGELOG entry under `3.x`
- [ ] All upstream examples present, file names Title Case
- [ ] `Usage.html.twig` + `Demo.html.twig` both present
- [ ] Visual + behavioral parity verified manually (screenshot/video attached)
- [ ] Snapshots regenerated + committed (no stale entries)
- [ ] Companion PR on `symfony/ux.symfony.com` linked
- [ ] `php-cs-fixer`, `twig-cs-fixer`, `pnpm run fmt`, `pnpm run lint` clean
- [ ] All Twig components have `{# @prop #}` + `{# @block #}` docblocks (if applicable)
- [ ] Trigger/Close sub-components use `<recipe>_<role>_attrs` (no wrapping `<button>`)
- [ ] `data-action` Stimulus actions piped through `|html_attr_type('sst')` when concatenable
- [ ] Inter-recipe deps declared in `manifest.json` `dependencies.recipe`
- [ ] No orphan snapshot files after rework/rename (`git status` clean after `--update-snapshots`)
- [ ] Every shipped file ends with trailing newline (`.html.twig`, `.json`, `.js`, `.css`, `.md`)

---

## Anti-patterns (flag in review)

| Anti-pattern | Fix |
| --- | --- |
| `{{ attributes }}` on root without `defaults` | `{{ attributes.defaults({...}) }}` |
| Hardcoded `class="..."` on root | `class="{{ ('<base> ' ~ attributes.render('class'))\|tailwind_merge }}"` |
| Variant via `{% if variant == ... %}` chains | `html_cva(base, variants).apply({...})\|tailwind_merge` |
| `Trigger.html.twig` wraps own `<button>` | Expose `<recipe>_trigger_attrs` + use `{%- block content %}{% endblock -%}` only |
| `data-action="click->x#y"` not piped | `'click->x#y'\|html_attr_type('sst')` |
| Missing `data-slot` on root/sub-roots (Shadcn) | Add `data-slot="<recipe>"` / `data-slot="<recipe>-<sub>"` |
| Missing `{# @prop #}` / `{# @block #}` docblocks | Add docblocks before `{%- props -%}` |
| Self-closing item reading `_parent_var` (outer-scope) | Use `provide()` in parent + `inject()` in child |
| Recipe depends on another recipe but `dependencies.recipe` empty | Declare it (e.g. `toggle-group` → `toggle`) |
| Snapshots not regenerated / partially stale | Regenerate via `simple-phpunit -d --update-snapshots` |
| Multiple recipes in one PR | Split into one PR per recipe |
| PR targets `2.x` | Retarget to `3.x`, move CHANGELOG entry |
| Missing companion PR on ux.symfony.com | Open it, link from recipe PR |
| Native `<details>`/`<summary>` when upstream has animation/ARIA parity | Replace with `<div>` + Stimulus controller |
| Subset of upstream examples | Reuse full set |
| `hidden` class for collapse/expand | `grid-template-rows: 0fr` + `overflow:hidden` + CSS transition |
| `group-hover` + `group-focus-within` for hover-triggered components | Stimulus controller with `openDelay`/`closeDelay` values |
| `in-data-[state=open]:visible` on nested open-state | Named Tailwind groups (`group/<recipe>-menu`, `group/<recipe>-sub`) |
| Orphan snapshots after recipe rework/rename | `git rm` stale files after `--update-snapshots` |

---

## Bad / Good

| Bad | Good |
| --- | --- |
| `<button class="..." data-action="click->dialog#open">{% block content %}{% endblock %}</button>` | `{%- set dialog_trigger_attrs = { 'data-action': 'click->dialog#open'\|html_attr_type('sst'), 'data-dialog-target': 'trigger', 'aria-haspopup': 'dialog' } -%}{%- block content %}{% endblock -%}` |
| `<div class="text-lg leading-none font-semibold {{ attributes.render('class') }}" {{ attributes }}>` | `<div class="{{ ('text-lg leading-none font-semibold ' ~ attributes.render('class'))\|tailwind_merge }}" {{ attributes.defaults({'data-slot': 'dialog-title'}) }}>` |
| `<twig:RadioGroup:Item value="a" />` reading `{% set _radio_group_name = ... %}` from parent | Keep `name` as explicit prop on `RadioGroup:Item` (self-closing) |
