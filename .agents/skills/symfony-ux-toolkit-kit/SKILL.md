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
            "twig/html-extra:^3.24.0",
            "symfony/ux-twig-component:^3.5",
            "tales-from-a-dev/twig-tailwind-extra:^1.3.0"
        ],
        "recipe": ["<other-recipe>"]
    }
}
```

Rules:
- Drop `assets/` from `copy-files` if no Stimulus controller.
- Add `"symfony/ux-icons"` to `composer` whenever templates use `<twig:ux:icon>`.
- Bump `twig/html-extra` to `^3.24.0` for `html_attr_type` / `tailwind_classes`. The `tailwind_classes` class-merge idiom also needs `tales-from-a-dev/twig-tailwind-extra:^1.3.0` and `symfony/ux-twig-component:^3.5` (enforced by `ComposerSymbolChecker`).
- Declare `dependencies.recipe` only for recipes required by the **component templates** themselves (e.g. `toggle-group` depends on `toggle`). Do NOT declare recipe deps for components used only in examples — examples are demo files, not shipped dependencies.
---

## Twig Component Patterns

### 1. Prop & block documentation (mandatory)

Document every prop with a `## <type> <Description.>` comment on the line above it **inside** the
`{% props %}` tag, and every rendered block with a `{##- <Description.> -#}` doc comment on its own
line right above it. These are Twig 3.29 documentation comments — Twig attaches them to the following
node as metadata, so they carry no runtime cost and the Toolkit reads them natively:

```twig
{%- props
    ## string Unique identifier used to generate internal Dialog IDs.
    id,
    ## boolean Whether the dialog is open on initial render.
    open = false
-%}
...
<div ...>
    {##- The dialog structure, typically includes `Dialog:Trigger` and `Dialog:Content`. -#}
    {%- block content %}{% endblock -%}
</div>
```

Format is enforced by `bin/ux-toolkit-kit-lint` (CI fails on any warning — see [Docblock linting](#docblock-linting)):

- **Prop `## <type> <Description.>`** — one per line, on the line directly above the prop name, inside `{% props %}`. Type first (camelCase name matches the declared prop), then the description.
- **Type** = valid PHPDoc/PHPStan type with **no spaces**: `'default'|'secondary'`, `string|array<string>|null`, `boolean`, `number`. A space breaks the type/description boundary, so `'a' | 'b'` is rejected — write `'a'|'b'`.
- **No `Defaults to`** in the documentation. Default values live **only** in `{%- props -%}` (single source of truth); the linter and ux.symfony.com read them from there.
- **Block `{##- <Description.> -#}`** — a doc comment (double `#`) on its own line directly above a block actually rendered in the template (`{% block x %}`, `block(outerBlocks.x)`, or `block('x')`). **Mirror the block's whitespace-trim** so the rendered output is unchanged: `{##- ... -#}` when the block opens with `{%-`/`{{-`, `{## ... -#}` when it opens with `{%`/`{{`. Never leave a rendered block undocumented.
- **Descriptions** start with a capital letter and end with a period.
- Reference sub-components by Twig tag name (`\`Dialog:Trigger\``).
- Requires `twig/twig >= 3.29` (documentation comments) and `symfony/ux-twig-component` with `PropsNode::getPropDocumentation()`.

### 2. Root element

There is one home for each kind of attribute. `attributes.defaults({...})` carries the consumer-overridable values: the merged **`class`** (as a `tailwind_classes` mergeable), `data-controller`, `data-action`, and genuinely overridable HTML defaults (`type: 'button'`, `alt: ''`). Everything that identifies or reflects the component's state is rendered **directly as a literal attribute**, so it is always present and can neither be dropped by the merge nor overridden:

- **`class`** → merged **inside `defaults()`**: `class: '<base>'|tailwind_classes` (or `class: style.apply({...})|tailwind_classes` with `html_cva`). `tailwind_classes` returns a mergeable value that `defaults()` merges with the consumer's `class` (consumer wins). No separate `class="..."` / `render('class')`.
  - **Exception — keep `('<base> ' ~ attributes.render('class'))|tailwind_merge`** when `class` and the attributes sink are on **different elements** (base on an outer element, sink on an inner one), or when `class` is spread onto an **external non-mergeable component** — e.g. `<twig:ux:icon>`, which validates `class` as a scalar string and rejects the `tailwind_classes` object. Spreading onto a **mergeable Toolkit child** (`<twig:Button>`, `<twig:Label>`, `<twig:Separator>`, …) is fine: `defaults()` chains the mergeables as `base < wrapper < caller`, matching React/Vue's `cn(base, className)`.
- **`data-slot`** → literal attribute (`data-slot="<recipe-name>"`). Structural Shadcn marker, never overridable.
- **State `data-*` and state `aria-*`** → literal attributes, **always emitted with an explicit value** (never `{% if %}`-guarded, never `x ? 'attr="y"'`): `data-state`, `data-open`, `data-closed`, `data-active`, `data-disabled`, `data-orientation`, `data-size`, `data-variant`, `data-side`, `data-selected`, `data-checked`; `aria-expanded`, `aria-selected`, `aria-hidden`, `aria-disabled`, `aria-checked`, `aria-pressed`, `aria-current`. Boolean values render as strings: `data-open="{{ open ? 'true' : 'false' }}"` (a bare `: false` renders empty/ambiguous — always use `: 'false'`).
- **Stimulus value attrs** (`data-<recipe>-<key>-value`), `id`, `role`, ARIA id-refs (`aria-controls`/`aria-labelledby`/`aria-describedby`) → literal attributes.
- **`class` + `data-controller` / `data-action` (+ overridable HTML defaults)** → `attributes.defaults({...})`. A bare `{{ attributes }}` remains only where there is no `class` base.

```twig
{# WITH a controller/action (interactive component) #}
<div
    id="{{ id }}"
    data-slot="<recipe-name>"
    data-<recipe>-<key>-value="{{ value }}"
    data-orientation="{{ orientation }}"
    aria-labelledby="{{ _<recipe>_title_id }}"
    {{ attributes.defaults({
        class: '<base classes>'|tailwind_classes,
        'data-controller': '<recipe>',
        'data-action': 'click-><recipe>#toggle',
    }) }}
>
    {%- block content %}{% endblock -%}
</div>

{# WITHOUT a controller/action (static component) #}
<div
    data-slot="<recipe-name>"
    {{ attributes.defaults({
        class: '<base classes>'|tailwind_classes,
    }) }}
>
    {%- block content %}{% endblock -%}
</div>
```

- Do NOT put `data-slot`, state `data-*`, `aria-*` or Stimulus `data-*-value` into `defaults()` — they belong as literal attributes (enforced by `AttributesDefaultsChecker`). Only `class`, `data-controller` and `data-action` belong in `defaults()`.
- Do NOT put hardcoded HTML element attributes (like `type="checkbox"`) into `defaults()` — those are structural, not overridable.
- **Structural / config / marker attributes stay conditional** (they are not "always render" state): `aria-orientation` on a decorative separator, `data-bs-parent`, presence-marker attributes like `data-horizontal`/`data-vertical`.
- **Non-Tailwind kits (Bootstrap, Common)** keep their own idiom: `class` is merged *inside* `defaults()` as a plain string (`attributes.defaults({class: '...'})`, no `tailwind_classes`), there is no `data-slot`, and no `tailwind_merge`. Only the `data-slot` rule and the state-attribute rule apply there; the linter's Tailwind-only checks are skipped for them.

### 3. Variant systems with `html_cva`

```twig
{%- set style = html_cva(
    base: '<base classes>',
    variants: {
        variant: { default: '...', outline: '...' },
        size: { default: '...', sm: '...', lg: '...' },
    },
) -%}
<button {{ attributes.defaults({ class: style.apply({variant: variant, size: size})|tailwind_classes }) }}>
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
{%- set dialog_trigger_attrs = {
    'data-action': 'click->dialog#open'|html_attr_type('sst'),
    'data-dialog-target': 'trigger',
    'aria-haspopup': 'dialog',
} -%}
{##- The trigger element (e.g., a `Button`) that opens the dialog when clicked. -#}
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
{%- props
    ## 'default'|'destructive' The visual style variant.
    variant = 'default'
-%}
<twig:Button variant="{{ variant }}" {{ ...attributes }}>
    {##- The action button label. -#}
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

Emit as **literal attributes** (outside `defaults()`), always present with an **explicit value** — never `{% if %}`-guarded, never `x ? 'attr="y"'` (see [section 2](#2-root-element)):
- `role`, `aria-haspopup`, `aria-expanded`, `aria-controls`, `aria-labelledby`, `aria-describedby`, `aria-disabled`, `aria-pressed`, `aria-hidden`, `aria-current`, `aria-selected`, `aria-checked`.
- `data-state="open|closed|active|inactive"`, `data-orientation="vertical|horizontal"`, `data-variant`, `data-size`, `data-disabled`, `data-open`, `data-closed`, `data-active`.
- Booleans render as strings: `data-open="{{ open ? 'true' : 'false' }}"`, `aria-expanded="{{ open ? 'true' : 'false' }}"` (a bare `: false` renders empty/ambiguous).
- IDs deterministic + shared between trigger/content via parent's `id` prop (e.g. `aria-controls={{ _accordion_item_content_id }}`).
- Structural/config attributes that are only valid in one state stay conditional (e.g. `aria-orientation` only when a separator is not decorative).

---

## Docblock linting

`bin/ux-toolkit-kit-lint` validates every component's prop and block documentation against the template. CI runs it per kit with `--fail-on-warning`, so any violation fails the build.

```bash
cd src/Toolkit
php bin/ux-toolkit-kit-lint --fail-on-warning kits/<kit>
```

Checks:
- **Prop `## <type> <Description.>`** — valid **spaceless** PHPStan type, description Capitalized + ending with a period. Every declared prop in `{%- props -%}` has a `## ...` doc comment above it and vice versa.
- **Block `{##- <Description.> -#}`** — description Capitalized + ending with a period. Every rendered block (`{% block x %}` / `block(outerBlocks.x)` / `block('x')`) has a `{##- ... -#}` doc comment on the line above it.
- Default values are **not** documented — they are read from `{%- props -%}`.

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

**Add the docblock only to controllers whose API you want shown.** It is a deliberate, per-controller choice — not a blanket requirement. Document a controller when its `data-controller`/`data-*` surface is meant to be used or overridden directly by the consumer (always true for a controller-only recipe). **Omit it** — leaving the controller undocumented and rendering no API reference — when the controller is an internal implementation detail the consumer never touches directly (they drive it through the recipe's Twig components, which carry their own prop/block documentation). Documenting such a controller would surface `data-*` internals as if they were public API.

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
- [ ] Docs: `## <type> <Description.>` above each prop in `{% props %}` + `{##- <Description.> -#}` on the line above each rendered block (trim mirrors the block); descriptions Capitalized + ending with a period; prop types are spaceless PHPStan types; **no `Defaults to`** (defaults live in `{%- props -%}`); every rendered block documented
- [ ] `attributes.defaults()` holds the merged `class` (`'<base>'|tailwind_classes`), `data-controller`/`data-action` (+ overridable HTML defaults); `data-slot`, state `data-*`, `aria-*` and Stimulus `data-*-value` are literal attributes; state attrs always emitted with an explicit value (`tailwind_merge` kept only in different-element / external-component exceptions)
- [ ] Trigger/Close sub-components use `<recipe>_<role>_attrs` (no wrapping `<button>`)
- [ ] `data-action` Stimulus actions piped through `|html_attr_type('sst')` when concatenable
- [ ] Inter-recipe deps declared in `manifest.json` `dependencies.recipe`
- [ ] No orphan snapshot files after rework/rename (`git status` clean after `--update-snapshots`)
- [ ] Every shipped file ends with trailing newline (`.html.twig`, `.json`, `.js`, `.css`, `.md`)

---

## Anti-patterns (flag in review)

| Anti-pattern | Fix |
| --- | --- |
| `{{ attributes.defaults({}) }}` with empty or no meaningful defaults | `{{ attributes }}` when no defaults needed; `{{ attributes.defaults({...}) }}` for the merged `class` (`tailwind_classes`), `data-controller`/`data-action` (+ overridable HTML defaults like `type`) |
| `data-slot`, `aria-*`, Stimulus `data-*-value` or state `data-*` inside `defaults()` | Render them as literal attributes outside `defaults()`; keep only `class`, `data-controller`/`data-action` in `defaults()` (enforced by `AttributesDefaultsChecker`) |
| State attr conditionally emitted (`{{ open ? 'data-state="open"' }}`) or bare `: false` (`data-open="{{ open ? 'true' : false }}"`) | Always emit with explicit string value (`data-state="{{ open ? 'open' : 'closed' }}"`, `... : 'false'`) |
| Separate `class="{{ ('<base> ' ~ attributes.render('class'))\|tailwind_merge }}"` + trailing `{{ attributes }}` (Tailwind kits) | Merge inside `defaults()`: `{{ attributes.defaults({ class: '<base>'\|tailwind_classes }) }}` (keep `tailwind_merge` only for different-element cases or spreads onto an external non-mergeable component like `<twig:ux:icon>`) |
| Variant via `{% if variant == ... %}` chains | `attributes.defaults({ class: html_cva(base, variants).apply({...})\|tailwind_classes })` |
| `Trigger.html.twig` wraps own `<button>` | Expose `<recipe>_trigger_attrs` + use `{%- block content %}{% endblock -%}` only |
| `data-action="click->x#y"` not piped | `'click->x#y'\|html_attr_type('sst')` |
| Missing `data-slot` on root/sub-roots (Shadcn) | Add `data-slot="<recipe>"` / `data-slot="<recipe>-<sub>"` |
| Missing `## <type> <desc>` prop comment / `{##- <desc> -#}` block comment | Add `## ...` above the prop in `{% props %}` / `{##- ... -#}` above the block |
| `Defaults to \`...\`` in a `## ...` prop comment | Remove it — the default lives only in `{%- props -%}` |
| Prop type with spaces (`'a' \| 'b'`) | Remove spaces (`'a'\|'b'`) |
| Docblock description not Capitalized / no trailing period | Capitalize + end with a period |
| Rendered block (`{% block x %}`) with no `{##- ... -#}` doc comment | Add `{##- <Description.> -#}` on the line above the block |
| Block doc comment that shifts rendered whitespace (wrong trim) | Mirror the block's trim: `{##- ... -#}` for `{%-`/`{{-`, `{## ... -#}` for `{%`/`{{` |
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
| `<div class="text-lg leading-none font-semibold {{ attributes.render('class') }}" {{ attributes.defaults({'data-slot': 'dialog-title'}) }}>` | `<div data-slot="dialog-title" {{ attributes.defaults({ class: 'text-lg leading-none font-semibold'\|tailwind_classes }) }}>` (`data-slot` literal; `class` merged inside `defaults()` via `tailwind_classes`) |
| `<twig:RadioGroup:Item value="a" />` reading `{% set _radio_group_name = ... %}` from parent | Keep `name` as explicit prop on `RadioGroup:Item` (self-closing) |
