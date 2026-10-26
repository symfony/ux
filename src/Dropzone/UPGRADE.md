# UPGRADE

## FROM 3.6 to 3.7

- The controller shows and hides the input, the placeholder, the preview and the image preview with the `hidden` attribute instead of inline styles, and the form theme renders `hidden` instead of `style="display: none"`.

    If you disabled the default stylesheet and your own CSS sets `display` on these parts (`.dropzone-input`, `.dropzone-placeholder`, `.dropzone-preview`, `.dropzone-preview-image`), your rule wins over the `hidden` attribute and the parts stay visible. Add this rule to your stylesheet:

    ```css
    .dropzone-container [hidden] {
        display: none !important;
    }
    ```

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
