# UPGRADE

## FROM 3.7 to 3.8

- The files over `max_files`, and in single-file mode the files after the first one, are now left out before `dropzone:pre-add`: its listeners only get the files that can be added. Before, they got every selected file, and the files over the limit were left out after their promise resolved.

- `dropzone:reject` is now dispatched before `dropzone:pre-add` and `dropzone:change`. Before, it was dispatched after `dropzone:change`. If a `dropzone:reject` listener reads the files of the input, it now gets the files from before the selection: read the rejected files from `event.detail.files` instead, or listen to `dropzone:change`.

- The image previews now use object URLs (`blob:`) instead of data URLs (`data:`), except for SVG images. If your Content Security Policy restricts images, allow `blob:` in `img-src`, or the previews are blocked:

    ```diff
    -Content-Security-Policy: img-src 'self' data:
    +Content-Security-Policy: img-src 'self' data: blob:
    ```

    The controller revokes these URLs when the file is removed or replaced, when the Dropzone is cleared, and when the controller disconnects. If your code copies the URL of a preview (its `src` or its background image) to another element, create your own URL from the file instead.

    The preview is now shown at once, instead of after the file is read.

- When a form is submitted while a `dropzone:pre-add` listener processes files, the Dropzone holds the submission and stops the `submit` event, so the listeners of the form and of the document do not see it. Once every Dropzone of the form is done, it submits the form again, with the last submit button used, and the listeners see that submission. A `submit` event dispatched by a script, or already cancelled by another listener, is not held. If your code holds the submission itself while `data-state` is `processing`, you can remove it.

## FROM 3.6 to 3.7

- The controller shows and hides the input, the placeholder, the preview and the image preview with the `hidden` attribute instead of inline styles, and the form theme renders `hidden` instead of `style="display: none"`.

    If you disabled the default stylesheet and your own CSS sets `display` on these parts (`.dropzone-input`, `.dropzone-placeholder`, `.dropzone-preview`, `.dropzone-preview-image`), your rule wins over the `hidden` attribute and the parts stay visible. Add this rule to your stylesheet:

    ```css
    .dropzone-container [hidden] {
        display: none !important;
    }
    ```

    Before 3.7, the controller set `display: flex` inline on the preview and `display: block` inline on the input when it showed them. It no longer does this. If you disabled the default stylesheet, set these `display` values in your own CSS to keep the same layout. The rule above still hides the parts when they must be hidden.

    If you override the `dropzone_widget` block in your form theme, replace `style="display: none"` with `hidden` on the preview and the image preview:

    ```diff
    -<div class="dropzone-preview" data-symfony--ux-dropzone--dropzone-target="preview" style="display: none">
    +<div class="dropzone-preview" data-symfony--ux-dropzone--dropzone-target="preview" hidden>
    ```

    The controller still removes that inline style when it connects, so the preview keeps working until you do.

- Passing the placeholder in the `attr` option of `DropzoneType` is deprecated, use the new `placeholder` option instead:

    ```diff
     $builder->add('photo', DropzoneType::class, [
    -    'attr' => ['placeholder' => 'Drop your photo here'],
    +    'placeholder' => 'Drop your photo here',
     ]);
    ```

    The placeholder is no longer rendered as a `placeholder` attribute on the `<input type="file">`, where it is not a valid attribute.

- If your field sets `accept` (for example with `'attr' => ['accept' => ...]`), the dropped files are now checked against it, and the files that do not match are left out. Before, only the file picker filtered the files.

    The type of a dropped file comes from the operating system and can differ from the one in `accept`: on Windows, a CSV file is often `application/vnd.ms-excel`, so `accept="text/csv"` now leaves out a dropped CSV file that the file picker shows. To fix this, add the extensions next to the MIME types, for example `.csv,text/csv`. To tell the user which files were left out, listen to the `dropzone:reject` event.

- In single-file mode, the controller keeps the file already in the input when it connects, as `multiple` mode does: it no longer empties the input and no longer dispatches `dropzone:clear` then. `dropzone:clear` is now also dispatched in `multiple` mode, when the last file is removed, and in both modes when the form is reset. If a `dropzone:clear` listener expects to run on connect, run that code from your own controller's `connect()` instead.

- The `onMultipleChange()` method of the controller is merged into `onInputChange()`, which now handles both modes. If you call it from a controller that extends the Dropzone one, call `onInputChange()` instead.

- In `multiple` mode, `dropzone:change` is no longer dispatched when a selection adds no file, because every file is already in the list or over `max_files`.
