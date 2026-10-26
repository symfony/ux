# CHANGELOG

## 3.7.0

- Add a `dropzone:pre-add` event to transform, replace or reject the selected files before they are added, and a `data-state="processing"` attribute on the container while it is pending
- Add a `data-state` attribute on the container: `empty`, `filled`, `dragging` or `processing`
- Show and hide the parts of the Dropzone with the `hidden` attribute instead of inline styles, see [UPGRADE.md](UPGRADE.md)
- Add a `dropzone_preview_item_template` form theme block and a `previewItemTemplate` target to render the preview of each file in `multiple` mode from a `<template>`
- Add a `placeholder` option to `DropzoneType`
- Deprecate passing the placeholder in the `attr` option of `DropzoneType`, use the `placeholder` option instead
- Leave out the dropped files that do not match the `accept` attribute of the input
- Add a `max_files` option to `DropzoneType` to limit the number of files in `multiple` mode, with a `Count` constraint when the Validator component is installed
- Add a `dropzone:reject` event with the files left out and the reason
- Use one code path for the single-file and `multiple` modes: the drag and drop, the preview template, the `dropzone:pre-add` event and the rejections behave the same in both modes
- Dispatch `dropzone:clear` whenever the Dropzone becomes empty: also when the last file is removed in `multiple` mode, and when the form is reset
- Keep the file already in the input when the controller connects in single-file mode, instead of emptying the input and dispatching `dropzone:clear`
- Show the `dragging` state only for drags that carry files, and keep it while the drag moves over the elements of the Dropzone
- Dispatch `dropzone:change` in `multiple` mode only when files are added, not when every selected file is a duplicate or over `max_files`

## 3.5.0

- Add support for Symfony 8.2's standalone `AssetMapperBundle`
- Add support for multiple file uploads: accumulate files across selections, preview each file, and remove them individually
- Add a `remove_label` option to label the per-file remove button in `multiple` mode
- Fix the drop zone appearing empty after dropping the very same file again, which Chrome reports without firing a `change` event
- Add support for Twig 4

## 3.0.0

- Minimum required Symfony version is now 7.4
- Minimum required PHP version is now 8.4

## 2.30

- Ensure compatibility with PHP 8.5

## 2.29.0

- Add Symfony 8 support

## 2.20

- Enable file replacement via "drag-and-drop"

## 2.13.2

- Revert "Change JavaScript package to `type: module`"

## 2.13.0

- Add Symfony 7 support.
- Change JavaScript package to `type: module`

## 2.9.0

- Add support for symfony/asset-mapper

## 2.7.0

- The JavaScript events now bubble up.

- Add `assets/src` to `.gitattributes` to exclude source TypeScript files from
  installing.

- TypeScript types are now included.

## 2.6.0

- [BC BREAK] The `assets/` directory was moved from `Resources/assets/` to `assets/`. Make
  sure the path in your `package.json` file is updated accordingly.

- The directory structure of the bundle was updated to match modern best-practices.

## 2.0

- Support for `stimulus` version 2 was removed and support for `@hotwired/stimulus`
  version 3 was added. See the [@symfony/stimulus-bridge CHANGELOG](https://github.com/symfony/stimulus-bridge/blob/main/CHANGELOG.md#300)
  for more details.
- Support added for Symfony 6
