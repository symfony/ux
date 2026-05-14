# CHANGELOG

## 0.1.0 — 2026-05-19

Initial release: CKEditor 5 bridge for `symfony/ux-editor`.

### Added

- `CKEditorBridge` (extends `AbstractWysiwygBridge`).
- `CKEditorConfig` (extends `AbstractWysiwygConfig`) with typed fields: `extraPlugins`, `removePlugins`, `heading`, `image`, `link`, `licenseKey`.
- `CKEditorTransformer` (extends `AbstractWysiwygTransformer`).
- Two presets: `wysiwyg.minimal` (bold/italic/link) and `wysiwyg.full` (full toolbar + heading config).
- Stimulus controller `symfony--ux-editor--ckeditor` with hot `readOnly` reload, dynamic import of `@ckeditor/ckeditor5-build-classic`, change-event wiring to `syncInput`.

### Notes

- Playwright E2E specs deferred to Plan 5 (demo app); the bridge has full PHPUnit + Vitest coverage of contract behavior.

### Requires

- `symfony/ux-editor` ^0.1.
- `@ckeditor/ckeditor5-build-classic` ^41 (peer dep). CKEditor 5 v44+ requires `licenseKey` — use `'GPL'` for OSS license.
