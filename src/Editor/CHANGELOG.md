# CHANGELOG

## 0.1.0 — 2026-05-19

Initial release of `symfony/ux-editor` core package.

### Added

- Tier 0 core: `EditorType`, `EditorContentInterface` + polymorphic value objects (`HtmlContent`, `BlockContent`, `PageContent`),
  `EditorContentFormat` enum, `BridgeInterface` + `BridgeRegistry`, `EditorConfigInterface` + `AbstractEditorConfig` + `CommonOptions` +
  `BridgeCapabilities`, preset registry, content converter registry.
- Tier 1 format abstracts: `AbstractWysiwygBridge` + `AbstractWysiwygConfig` + `AbstractWysiwygTransformer` + `WysiwygCapabilities`
  (same shape for Block and Page families).
- Doctrine custom types: `editor_html`, `editor_blocks`, `editor_page`.
- Upload pipeline: `EditorUploadController`, `SignedUploadUrlGenerator`, `EditorUploadHandlerInterface`,
  `DefaultLocalUploadHandler`, `UploadHandlerRegistry`.
- Twig: `ux_editor_render` function (HTML sanitized, blocks via registry, page in sandboxed iframe).
- LiveComponent: `LiveEditor` trait (debounced `saveDraft` + dirty/lastSavedAt tracking).
- JS Tier 0/1: `AbstractEditorController` + format-specific abstract controllers, `SignedUploadClient`, `setupAutosave`, content mirrors.
- Bundle config: `ux_editor.html.sanitize_required`, `ux_editor.upload.default_profile`, `ux_editor.upload.ttl_seconds`.
- Compile pass `AssertSanitizerPass` (boot-time check for required HTML sanitizer).
- Console command `debug:ux-editor`.
- WebProfiler data collector `ux_editor`.
- Editor bridges shipped as separate composer + npm sub-packages:
  `symfony/ux-editor-ckeditor` (CKEditor 5, WYSIWYG family),
  `symfony/ux-editor-editorjs` (EditorJS, block family) and
  `symfony/ux-editor-grapesjs` (GrapesJS, page-builder family), each with its
  own presets, config and Stimulus controller.

### Notes

The core package ships editor-agnostic abstractions; concrete editors plug in
through `BridgeInterface` + `BridgeRegistry` and are distributed as the
sub-packages listed above.
