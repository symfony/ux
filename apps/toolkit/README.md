# UX Toolkit previews

Renders every `{"preview": true}` example of the UX Toolkit kits in isolation, with the preview assets of its kit, in light and dark mode.

## Run it

```shell
symfony php ../../.github/build-packages.php
symfony composer install
symfony serve
```

`composer install` also clears the cache, installs the importmap packages and builds the stylesheet of every kit. Run `symfony composer run preview:build` to do it again. The app listens on http://127.0.0.1:9889, and `symfony serve` rebuilds the kit stylesheets while you edit a kit.

- `/` lists the examples.
- `php bin/console app:examples` prints them as JSON.
- `/{kit}/{recipe}/{example}?theme=light|dark` renders one example. `{example}` is the slug of the heading placed right above it in the recipe's `README.md`. If the example sits directly under the title instead, use `default`.

## Choose the kits

`UX_TOOLKIT_PREVIEW_KITS` lists the kits to preview, separated like `PATH`: names of kits shipped with the Toolkit, or directories of external kits. Without it, every kit shipped with the Toolkit is previewed.

To preview an external kit alone, require the importmap packages it declares, then build the previews again:

```shell
export UX_TOOLKIT_PREVIEW_KITS=/path/to/my-kit
php bin/console importmap:require <the packages of the kit>
symfony composer run preview:build
```

A kit is named after its directory, and two previewed kits cannot share a name.

## Visual tests

The Toolkit's Playwright tests screenshot every example in light and dark mode. They compare each screenshot with the baseline committed next to the recipe, in `kits/<kit>/<recipe>/tests/screenshots/`. Chromium runs in Docker. Docker must be running. Playwright starts this app and the browser. It reuses a server if one is already running. From the repository root:

```shell
# Everything
pnpm exec playwright test -c src/Toolkit/assets

# One recipe
pnpm exec playwright test -c src/Toolkit/assets --grep shadcn/popover

# After a visual change, update the snapshots and screenshots of the recipe
bin/update_toolkit_tests.sh shadcn/popover
```

Interactive recipes also have their own spec in `kits/<kit>/<recipe>/tests/`. Each spec is written with `describeRecipe()` and `testState()` from `src/Toolkit/assets/test/browser/fixtures.ts`. `src/Toolkit/assets/test/browser/interactions.spec.ts` lists the interactive recipes that still have no spec.
