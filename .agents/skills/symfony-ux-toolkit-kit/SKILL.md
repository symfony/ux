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

Author + review recipes for UX Toolkit. Recipes = unit shipped to end-users (Twig components + optional Stimulus controllers). Each recipe carries a `README.md` — its single doc source (description, live-preview examples, install/API), rendered as-is on ux.symfony.com.

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
4. **Reuse all upstream examples.** No subset. Read both component source **and** every upstream example, then inline each as a live-preview block in the recipe `README.md` (see [Examples](#examples-conventions)).
5. **Companion PR on `symfony/ux.symfony.com`** only when the recipe ships a Stimulus controller (register it) or new Tailwind classes (rebuild CSS) — the docs page renders automatically from the recipe `README.md`, no per-recipe template. Link in recipe PR body when opened.
6. **Regenerate snapshots** after every recipe change + commit. CI + reviewers reject stale snapshots.
7. **Use GitHub PR template** (Bug fix / Feature / License: MIT / Issues: Part of #3233). Fabbot fails otherwise.
8. **Prefer Stimulus controller** over native browser features (e.g. `<details>`) when parity needs animations, ARIA sync, coordinated state. Native fine only when matches upstream UX exactly.

---

## Recipe Directory Layout

```
src/Toolkit/kits/<kit>/<recipe>/
├── manifest.json
├── README.md                              # single doc source: description + inline live-preview examples
├── templates/components/
│   ├── <Component>.html.twig              # root component
│   └── <Component>/<SubName>.html.twig    # e.g. Trigger, Close, Header, Item, Content
└── assets/controllers/                    # optional, only if interactive behavior is needed
    └── <recipe>_controller.js
```

Sub-component file path `Component/SubName.html.twig` consumed as `<twig:Component:SubName>`.

There is **no `examples/` directory** — examples live inline in `README.md` (see [Examples](#examples-conventions)). Recipe `copy-files` only copies `templates/` (+ `assets/`); the README is doc-only, never shipped to the user's app.

### `README.md` structure

````markdown
# <Human Name>

<One-sentence description.>    <!-- first paragraph; feeds getDescription() + manifest -->

```twig {"preview":true,"height":"300px"}
<!-- hero preview: rich showcase, was Demo.html.twig -->
<twig:Recipe ... />
```

## Installation

::: installation    <!-- directive, expanded by the renderer -->

## Usage

```twig
<!-- STATIC block (no preview): minimal API surface, was Usage.html.twig -->
<twig:Recipe prop="a | b" />
```

## Examples

### <Variant Name>

<Optional sentence describing the variant.>

```twig {"preview":true,"height":"150px"}
<!-- one live-preview block per upstream example -->
<twig:Recipe ... />
```

### RTL    <!-- always last Examples subsection (see RTL examples) -->

## API Reference

::: api-reference    <!-- directive, expanded by the renderer -->
```
````

Info-string options on a preview block (JSON after the language):
- **`"preview":true`** — required marker that turns the block into a live iframe + Code tab. Without it the block is a plain static snippet.
- **`"height":"<px>"`** — iframe height (e.g. `"150px"`, `"300px"`); default `200px`.
- **`"collapseClass":true`** — collapse long `class="..."` attributes in the Code tab (use for examples with long Tailwind class lists, e.g. `post-link`).

Only two directives survive in READMEs: `::: installation` and `::: api-reference`. The old `::: example <Name>` directive (referencing `examples/<Name>.html.twig`) is **gone**.

---

## Shadcn UI

Always emit `data-slot="<recipe-name>"` on root + `data-slot="<recipe-name>-<sub>"` on every sub-component root. Shadcn-specific convention driven by its CSS selectors.

### Upstream sources

Read all source files per recipe: component source carries canonical classes + `data-*` surface; examples show usage patterns; MDX drives docs and manifest.

| File | Purpose |
| --- | --- |
| `apps/v4/styles/radix-nova/ui/<recipe>.tsx` | **Component source** — canonical Tailwind classes, sub-component structure, `data-slot`/`data-state` surface |
| `apps/v4/styles/radix-nova/ui-rtl/<recipe>.tsx` | **RTL variant** — classes that differ per text direction (see [RTL class variants](#rtl-class-variants)) |
| `apps/v4/examples/radix/<recipe>-*.tsx` | **Usage examples** — one file per variant, drives examples list |
| `apps/v4/content/docs/components/radix/*.mdx` | **Docs + manifest metadata** — single source of truth for titles, descriptions, section order; `description` copied verbatim to `ux/src/Toolkit/kits/shadcn/**/manifest.json` |

Enumerate every example file for recipe:

```bash
gh api "repos/shadcn-ui/ui/git/trees/main?recursive=1" --jq '.tree[].path' \
  | grep "apps/v4/examples/radix/<recipe>"
```

Fetch each:
```
https://raw.githubusercontent.com/shadcn-ui/ui/refs/heads/main/apps/v4/examples/radix/<example>.tsx
```

### RTL class variants

The canonical source provides two implementations per component:

* `ui/apps/v4/styles/radix-nova/ui/{component}.tsx` — LTR (default)
* `ui/apps/v4/styles/radix-nova/ui-rtl/{component}.tsx` — RTL

Always diff both files. For each class that differs between LTR and RTL, apply the `rtl:` Tailwind variant **in addition** to the LTR class — do not create separate templates.

Use `ltr:` to scope the LTR class when RTL replaces it with a different one. Keep `rtl:` classes from the RTL file verbatim when they are already prefixed (e.g. `rtl:translate-x-1/2`).

Example: if `ui/table.tsx` has `[&:has([role=checkbox])]:pr-0` and `ui-rtl/table.tsx` has `[&:has([role=checkbox])]:pe-0`, write:
```
ltr:[&:has([role=checkbox])]:pr-0 rtl:[&:has([role=checkbox])]:pe-0
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

The docs page renders **automatically** from the recipe `README.md` (`RecipeDocRenderer` — no per-recipe `.md.twig`, no `toolkit_code_example`). A companion PR on `symfony/ux.symfony.com` is needed **only** when:

- **The recipe ships a Stimulus controller** — add the import to `assets/toolkit-<kit>.js` (e.g. `import Alert from '@symfony/ux-toolkit/kits/<kit>/<recipe>/assets/controllers/<recipe>_controller.js';`) so the live preview can boot it.
- **The recipe introduces new Tailwind classes** — run `symfony php bin/console tailwind:build` + commit the CSS output so previews render correctly.

Attach screenshot/video of every interactive state. Link the companion PR URL in the recipe PR body before requesting review. A pure-template recipe with no new classes needs **no** companion PR.

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
- Declare `dependencies.recipe` only for recipes required by the **component templates** themselves (e.g. `toggle-group` depends on `toggle`). Do NOT declare recipe deps for components used only in examples — examples are demo files, not shipped dependencies.
---

## Twig Component Patterns

### 1. Header docblock (mandatory)

One `{# @prop ... #}` per declared prop + one `{# @block ... #}` per block the component renders:

```twig
{# @prop id string Unique identifier used to generate internal Dialog IDs. #}
{# @prop open boolean Whether the dialog is open on initial render. #}
{# @block content The dialog structure, typically includes `Dialog:Trigger` and `Dialog:Content`. #}
{%- props id, open = false -%}
```

Format is enforced by `bin/ux-toolkit-kit-lint` (CI fails on any warning — see [Docblock linting](#docblock-linting)):

- **`@prop <name> <type> <Description.>`** — name first (camelCase Twig variable, `[a-z][a-zA-Z0-9]*`), then type, then description.
- **Type** = valid PHPDoc/PHPStan type with **no spaces**: `'default'|'secondary'`, `string|array<string>|null`, `boolean`, `number`. A space breaks the type/description boundary, so `'a' | 'b'` is rejected — write `'a'|'b'`.
- **No `Defaults to`** in the docblock. Default values live **only** in `{%- props -%}` (single source of truth); the linter and ux.symfony.com read them from there.
- **`@block <name> <Description.>`** — name must match a block actually rendered in the template (`{% block x %}`, `block(outerBlocks.x)`, or `block('x')`). Never document a slot the component doesn't render, and never leave a rendered block undocumented.
- **Descriptions** start with a capital letter and end with a period.
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
{# @block content The trigger element (e.g., a `Button`) that opens the dialog when clicked. #}
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
{# @prop variant 'default'|'destructive' The visual style variant. #}
{# @block content The action button label. #}
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

## Docblock linting

`bin/ux-toolkit-kit-lint` validates every component's `@prop`/`@block` docblocks against the template. CI runs it per kit with `--fail-on-warning`, so any violation fails the build.

```bash
cd src/Toolkit
php bin/ux-toolkit-kit-lint --fail-on-warning kits/<kit>
```

Checks:
- **`@prop`** — camelCase name, valid **spaceless** PHPStan type, description Capitalized + ending with a period. Every `@prop` maps to a prop in `{%- props -%}` and vice versa.
- **`@block`** — valid Twig block name, description Capitalized + ending with a period. Every `@block` maps to a rendered block (`{% block x %}` / `block(outerBlocks.x)` / `block('x')`) and vice versa.
- Default values are **not** documented in the docblock — they are read from `{%- props -%}`.

---

## Stimulus Controller Conventions

`assets/controllers/<recipe>_controller.js`:

```js
import { Controller } from '@hotwired/stimulus';

/**
 * @value  open     Whether the component is open on initial render.
 * @target trigger  The element that toggles the component and reflects its expanded state.
 * @target content  The region shown or hidden when the component toggles.
 * @action open     Opens the component.
 * @action close    Closes the component.
 */
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

### Controller docblocks (API reference)

A controller's public API is documented with a `/** ... */` docblock placed **immediately before `export default class`**, using `@value`/`@target`/`@action` tags. `RecipeDocRenderer` renders these under `::: api-reference` (the same block that documents Twig component props), which is the only way a **controller-only recipe** — one with no `templates/components/*.html.twig` — surfaces an API reference. `bin/ux-toolkit-kit-lint` validates them via `StimulusControllerDocChecker` (see [Docblock linting](#docblock-linting)).

**Add the docblock only to controllers whose API you want shown.** It is a deliberate, per-controller choice — not a blanket requirement. Document a controller when its `data-controller`/`data-*` surface is meant to be used or overridden directly by the consumer (always true for a controller-only recipe). **Omit it** — leaving the controller undocumented and rendering no API reference — when the controller is an internal implementation detail the consumer never touches directly (they drive it through the recipe's Twig components, which carry their own `@prop`/`@block` docs). Documenting such a controller would surface `data-*` internals as if they were public API.

- **Format:** `@<tag> <name> <Description.>` — name first, then a one-sentence description that starts Capitalized and ends with a period. Align columns for readability (whitespace is normalized). Do **not** document types or defaults in the docblock — the renderer reads value types/defaults from the `static values` declaration.
- **`@value <name>`** — one per key in `static values`. For object-form values (`open: { type: Boolean, default: false }`), document the **top-level key** (`open`), never the inner `type`/`default`.
- **`@target <name>`** — one per string in `static targets`.
- **`@action <name>`** — opt-in: document only the **public methods actually wired via `data-action`** in the recipe's templates (find them with `grep -rhoE '<identifier>#[a-zA-Z]+' templates`). Never document private methods (`_`/`#` prefixed) or lifecycle methods (`connect`/`disconnect`). Every `@action` must match a real method.
- **All-or-nothing once you opt in:** a controller with **no tags at all** renders no API reference and is left untouched by the linter. But once **any** tag is present, the linter requires every `static values` key and every `static targets` string to have a matching tag (and vice versa) — partial documentation fails CI. So the choice is per-controller: document its whole surface, or leave it entirely undocumented.
- The controller identifier is derived from the filename (`<recipe>_controller.js` → `<recipe>`; nested `_` → `-`, e.g. `alert_dialog_controller.js` → `alert-dialog`), and each value's `data-*` attribute is derived from it (`autoClose` on `widget` → `data-widget-auto-close-value`).

---

## Examples Conventions

Examples are **inline in `README.md`**, not separate files. Each is an `### <Variant Name>` subsection under `## Examples`, followed by an optional one-sentence description and a live-preview block:

````markdown
### With Icon

You can render an icon inside the badge.

```twig {"preview":true,"height":"150px"}
<twig:Badge variant="secondary">
    <twig:ux:icon name="lucide:badge-check" data-icon="inline-start" />
    Verified
</twig:Badge>
```
````

- Heading is **Title Case with spaces** (`With Icon`, `Custom Colors`, `Different Sizes`, `File Tree`).
- **Two mandatory blocks live outside `## Examples`:** the **hero preview** right after the description (rich showcase, replaces `Demo.html.twig`) and the **`## Usage`** static ` ```twig ` block — minimal call surface, no `preview`, replaces `Usage.html.twig`.
- One `### <Variant>` per upstream variant. Match upstream copy/structure where possible.
- When upstream uses cross-cutting JS (e.g. shadcn's `language-selector`), replicate intent without inventing new infrastructure (e.g. stack two independent components in one block, see collapsible's `### RTL`).

### RTL examples

- RTL is the **`### RTL`** subsection under `## Examples` — always last, `###` (not `##`).
- The preview block must show **both the Arabic and Hebrew versions** (`dir="rtl"`), stacked vertically — no side-by-side LTR/RTL comparison.
- No LTR card: it duplicates the hero preview and adds no value.
- The subsection description must always be: `To enable RTL support, set the \`dir="rtl"\` attribute on the root element.`

---

## Tests & Snapshots

`ComponentsRenderingTest` renders every preview block from each recipe `README.md` and snapshots it, keyed by **example index** (`... Kit shadcn, component badge, example 5__1.html`) — position in the README, not a file name.

```bash
cd src/Toolkit

# When examples were removed/reordered, blow away the recipe's snapshots first
rm -fr "tests/Functional/__snapshots__/"*"component <recipe>"*

# Regenerate (simple-phpunit is gone — use phpunit; -d passes the flag through to the snapshot lib)
php vendor/bin/phpunit -d --update-snapshots

# Re-run normally to confirm green
php vendor/bin/phpunit

git add tests/Functional/__snapshots__
```

Reviewers explicitly check snapshots regenerated (`#3488`).

**Orphan snapshots:** removing or reordering examples shifts the trailing indexes, so the highest-numbered `... example N__1.html` files stop regenerating + silently persist. After regenerating, inspect `git status` for leftover files + `git rm` them.

**After rebase on `3.x`:** snapshot formatter may have evolved upstream. Re-run `--update-snapshots` once more after final rebase to avoid "diff in snapshots" CI failures.

---

## Authoring Workflow

1. Locate upstream reference (see [Shadcn UI](#shadcn-ui) / [Flowbite v4](#flowbite-v4)) — list every example variant before writing any code
2. Scaffold recipe directory + `manifest.json`
3. Root component, sub-components (with `<recipe>_<role>_attrs`), Stimulus controller if needed
4. Write `README.md`: description + hero preview, `## Installation` (`::: installation`), `## Usage` static block, `## Examples` with one `### <Variant>` live-preview per upstream example (+ `### RTL` last), `## API Reference` (`::: api-reference`)
5. Snapshots — regenerate, inspect HTML diff, commit
6. Lint/format, CHANGELOG entry, open PR + companion PR (only if JS/CSS — see [Companion PR](#companion-pr-on-uxsymfonycom))

---

## PR / Review Checklist

- [ ] Single recipe per PR
- [ ] Targets `3.x`
- [ ] PR template filled (Bug/Feature, License: MIT, Issues: Part of #3233)
- [ ] CHANGELOG entry under `3.x`
- [ ] All upstream examples present as inline `{"preview":true}` blocks in `README.md`, `### <Variant>` headings Title Case
- [ ] `README.md` has the hero preview + `## Usage` static block + `::: installation` / `::: api-reference` directives
- [ ] Visual + behavioral parity verified manually (screenshot/video attached)
- [ ] Snapshots regenerated + committed (no stale entries)
- [ ] Companion PR on `symfony/ux.symfony.com` linked **only if** recipe ships JS or new Tailwind classes
- [ ] `php-cs-fixer`, `twig-cs-fixer`, `pnpm run fmt`, `pnpm run lint` clean
- [ ] `bin/ux-toolkit-kit-lint --fail-on-warning kits/<kit>` clean
- [ ] Docblocks: `@prop`/`@block` present; descriptions Capitalized + ending with a period; prop types are spaceless PHPStan types; **no `Defaults to`** (defaults live in `{%- props -%}`); every `@block` matches a rendered block
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
| `Defaults to \`...\`` in a `@prop` docblock | Remove it — the default lives only in `{%- props -%}` |
| Prop type with spaces (`'a' \| 'b'`) | Remove spaces (`'a'\|'b'`) |
| Docblock description not Capitalized / no trailing period | Capitalize + end with a period |
| `@block` documenting a slot the component never renders | Remove the `@block` |
| Rendered block (`{% block x %}`) with no `@block` docblock | Add the `@block` |
| Self-closing item reading `_parent_var` (outer-scope) | Use `provide()` in parent + `inject()` in child |
| Recipe depends on another recipe but `dependencies.recipe` empty | Declare it (e.g. `toggle-group` → `toggle`) |
| Snapshots not regenerated / partially stale | Regenerate via `phpunit -d --update-snapshots` (not `simple-phpunit` — removed) |
| Multiple recipes in one PR | Split into one PR per recipe |
| PR targets `2.x` | Retarget to `3.x`, move CHANGELOG entry |
| Companion PR opened for a docs-only recipe | Skip it — docs render from `README.md`; companion only for JS/CSS |
| Native `<details>`/`<summary>` when upstream has animation/ARIA parity | Replace with `<div>` + Stimulus controller |
| Example as a separate `examples/*.html.twig` file or `::: example` directive | Inline as a ` ```twig {"preview":true} ` block in `README.md` |
| Subset of upstream examples | Reuse full set, inline in `README.md` |
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
