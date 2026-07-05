# CHANGELOG

## 3.3.0

- Lint component docblocks: check the `{# @prop #}` and `{# @block #}` format and their consistency with the `{% props %}` declaration and the rendered blocks
- [Shadcn] Add `combobox` recipe
- [Shadcn] Add `sonner` recipe

## 3.2.0

- Add lint kit command `bin/ux-toolkit-kit-lint` to check for issues in local kits
- Allow declaring kit-global dependencies in a kit manifest
- Harden recipe installer against path traversal (security fix).
- Fix dependencies in several Shadcn recipe manifests
- Fix dependencies in several Flowbite v4 recipe manifests
- Fix position and remove phantom text node in the Shadcn `tooltip` recipe

## 3.1

- [Flowbite] Add `avatar` recipe
- [Flowbite] Add `dropdown` recipe
- [Shadcn] Add `hover-card` recipe
- [Shadcn] Add `resizable` recipe
- [Shadcn] Rework `accordion` recipe to use `provide()`/`inject()`
- [Shadcn] Rework `alert-dialog` recipe to use `provide()`/`inject()`
- [Shadcn] Rework `collapsible` recipe to use `provide()`/`inject()`
- [Shadcn] Rework `dialog` recipe to use `provide()`/`inject()`
- [Shadcn] Rework `tabs` recipe to use `provide()`/`inject()`
- [Shadcn] Rework `toggle-group` recipe to use `provide()`/`inject()`
- [Shadcn] Rework `tooltip` recipe to use `provide()`/`inject()`
- [Shadcn] Align `accordion` with shadcn reference
- [Shadcn] Align `alert` with shadcn reference
- [Shadcn] Align `alert-dialog` with shadcn reference
- [Shadcn] Align `aspect-ratio` with shadcn reference
- [Shadcn] Align `avatar` with shadcn reference
- [Shadcn] Align `badge` with shadcn reference
- [Shadcn] Align `button` with shadcn reference
- [Shadcn] Align `button-group` with shadcn reference
- [Shadcn] Align `breadcrumb` with shadcn reference
- [Shadcn] Align `card` with shadcn reference
- [Shadcn] Align `checkbox` with shadcn reference
- [Shadcn] Align `collapsible` with shadcn reference
- [Shadcn] Align `dialog` with shadcn reference
- [Shadcn] Align `empty` with shadcn reference
- [Shadcn] Align `field` with shadcn reference
- [Shadcn] Align `hover-card` with shadcn reference
- [Shadcn] Align `input` with shadcn reference
- [Shadcn] Align `input-group` with shadcn reference
- [Shadcn] Align `item` with shadcn reference
- [Shadcn] Align `kbd` with shadcn reference
- [Shadcn] Align `label` with shadcn reference
- [Shadcn] Align `pagination` with shadcn reference
- [Shadcn] Align `progress` with shadcn reference
- [Shadcn] Align `radio-group` with shadcn reference
- [Shadcn] Align `separator` with shadcn reference
- [Shadcn] Align `skeleton` with shadcn reference
- [Shadcn] Align `spinner` with shadcn reference
- [Shadcn] Align `switch` with shadcn reference
- [Shadcn] Align `table` with shadcn reference
- [Shadcn] Align `tabs` with shadcn reference
- [Shadcn] Align `textarea` with shadcn reference
- [Shadcn] Align `toggle` with shadcn reference
- [Shadcn] Align `toggle-group` with shadcn reference
- [Shadcn] Align `tooltip` with shadcn reference

## 3.0.0

- Minimum required Symfony version is now 7.4
- Minimum required PHP version is now 8.4
- [Shadcn] Add `radio-group` recipe
- [Shadcn] Add `collapsible` recipe
- [Shadcn] Add `typography` recipe
- [Shadcn] Add `toggle-group` recipe

## 2.36.1

- Harden recipe installer against path traversal (security fix).

## 2.35

- [Flowbite] Add Flowbite v4 kit
- [Shadcn] Add `toggle` recipe
- [Shadcn] Use `html_attr_type` filter from `twig/html-extra:^3.24` for composable trigger attributes
- [Shadcn] Rename `trigger_attrs` to `alert_dialog_trigger_attrs` in `AlertDialog:Trigger`
- [Shadcn] Rename `trigger_attrs` to `dialog_trigger_attrs` in `Dialog:Trigger`
- [Shadcn] Rename `close_attrs` to `dialog_close_attrs` in `Dialog:Close`
- [Shadcn] Rename `trigger_attrs` to `tooltip_trigger_attrs` in `Tooltip:Trigger`
- Allow Symfony UX 3.x packages

## 2.33.0

- [Shadcn] Add `accordion` recipe
- [Shadcn] Add `tabs` recipe
- [Shadcn] Add `tooltip` recipe
- [Shadcn] Rework templates of `alert` recipe
- [Shadcn] Rework templates of `avatar` recipe
- [Shadcn] Rework templates of `badge` recipe
- [Shadcn] Rework templates of `button` recipe
- [Shadcn] Rework templates of `card` recipe
- [Shadcn] Rework templates of `input-group` recipe
- [Shadcn] Rework templates of `table` recipe
- [Shadcn] Rework templates of `textarea` recipe
- [Shadcn] Add `Avatar:Badge` component
- [Shadcn] Add `Avatar:Fallback` component
- [Shadcn] Add `Avatar:Group` component
- [Shadcn] Add `Avatar:GroupCount` component
- [Shadcn] Add `Alert:Action` component
- [Shadcn] Remove `Avatar:Text` component

## 2.29.0

- Add Symfony 8 support

## 2.25

- Package added
