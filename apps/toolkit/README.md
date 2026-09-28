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
- `/examples.json` returns them as JSON.
- `/{kit}/{recipe}/{index}?theme=light|dark` renders one of them.

## Choose the kits

`UX_TOOLKIT_PREVIEW_KITS` lists the kits to preview, separated like `PATH`: names of kits shipped with the Toolkit, or directories of external kits. Without it, every kit shipped with the Toolkit is previewed.

To preview an external kit alone, require the importmap packages it declares, then build the previews again:

```shell
export UX_TOOLKIT_PREVIEW_KITS=/path/to/my-kit
php bin/console importmap:require <the packages of the kit>
symfony composer run preview:build
```

A kit is named after its directory, and two previewed kits cannot share a name.
