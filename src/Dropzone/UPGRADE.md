# UPGRADE

## FROM 3.6 to 3.7

- Passing the placeholder in the `attr` option of `DropzoneType` is deprecated, use the new `placeholder` option instead:

    ```diff
     $builder->add('photo', DropzoneType::class, [
    -    'attr' => ['placeholder' => 'Drop your photo here'],
    +    'placeholder' => 'Drop your photo here',
     ]);
    ```

    The placeholder is no longer rendered as a `placeholder` attribute on the `<input type="file">`, where it is not a valid attribute.
