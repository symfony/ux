# CHANGELOG

## 0.1.0 — 2026-05-19

Initial release: GrapesJS bridge for `symfony/ux-editor`.

### Added

- `GrapesJSBridge` (extends `AbstractPageBuilderBridge`).
- `GrapesJSConfig` (extends `AbstractPageConfig`) with typed fields: `components`, `blocks`, `storageManager`, `deviceManager`, `canvasCss`.
- `GrapesJSTransformer` (extends `AbstractPageTransformer`).
- `PageBuilderLandingPreset` (`name: page_builder.landing`) with hero/section/text/image blocks + responsive devices.
- Stimulus controller `symfony--ux-editor--grapesjs` with dynamic import of `grapesjs`, change-event wiring (component/asset add/remove/update), Collection-to-array adapter for `getComponents`/`getAssets`.

### Notes

- Playwright E2E specs deferred to Plan 5 (demo app); the bridge has full PHPUnit + Vitest coverage of contract behavior.

### Requires

- `symfony/ux-editor` ^0.1.
- `grapesjs` ^0.21 (peer dep).
