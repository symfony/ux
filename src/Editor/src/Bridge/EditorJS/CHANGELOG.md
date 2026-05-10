# CHANGELOG

## 0.1.0 — 2026-05-19

Initial release: EditorJS bridge for `symfony/ux-editor`.

### Added

- `EditorJSBridge` (extends `AbstractBlockBridge`).
- `EditorJSConfig` (extends `AbstractBlockConfig`) with `tools`, `defaultBlock`, `minHeight`, `logLevel`.
- `ToolDefinition` DTO for typed EditorJS tool config.
- `EditorJSTransformer` (extends `AbstractBlockTransformer`).
- Five default block renderers: `ParagraphRenderer`, `HeaderRenderer`, `ListRenderer`, `ImageRenderer`, `QuoteRenderer`.
- `BlogStandardPreset` (`name: blog.standard`).
- Stimulus controller `symfony--ux-editor--editorjs` with hot config reload for `readOnly` + `placeholder`,
  dynamic import of `@editorjs/editorjs`, tool class resolution via `window.UXEditorJSTools`.

### Notes

- Playwright E2E specs deferred to Plan 5 (demo app); the bridge has full PHPUnit + Vitest coverage of contract behavior.

### Requires

- `symfony/ux-editor` ^0.1.
- `@editorjs/editorjs` ^2.30 (peer dep).
