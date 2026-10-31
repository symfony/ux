Symfony UX Dropzone
===================

Symfony UX Dropzone is a Symfony bundle providing light dropzones for
file inputs in Symfony Forms. It is part of `the Symfony UX initiative`_.

It allows visitors to drag and drop files into a container instead of
having to browse their computer for a file.

Installation
------------

.. caution::

    Before you start, make sure you have `StimulusBundle configured in your app`_.

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-dropzone

If you're using WebpackEncore, install your assets and restart Encore (not
needed if you're using AssetMapper):

.. code-block:: terminal

    $ npm install --force
    $ npm run watch

.. note::

    For more complex installation scenarios, you can install the JavaScript assets through the `@symfony/ux-dropzone npm package`_

Usage
-----

The most common usage of Symfony UX Dropzone is to use it as a drop-in
replacement of the native FileType class::

    // ...
    use Symfony\UX\Dropzone\Form\DropzoneType;

    class CommentFormType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options): void
        {
            $builder
                // ...
                ->add('photo', DropzoneType::class)
                // ...
            ;
        }

        // ...
    }

Changing the placeholder
~~~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 3.7

    The ``placeholder`` option was added in Dropzone 3.7.
    Before, the placeholder was passed in the ``attr`` option.

The text shown in the Dropzone is ``'Drag and drop or browse'`` by default.
Change it with the ``placeholder`` option, which goes through the form's translation domain and also accepts a ``TranslatableInterface`` object.
Pass ``false`` to show no text.
When you pass your own ``attr`` option, the default text is not used: pass the ``placeholder`` option too::

    ->add('photo', DropzoneType::class, [
        'placeholder' => 'Drop your photo here',
    ])

Uploading multiple files
~~~~~~~~~~~~~~~~~~~~~~~~

Upload several files at once, pass the standard ``multiple``
option (inherited from ``FileType``)::

    // ...
    use Symfony\UX\Dropzone\Form\DropzoneType;

    class CommentFormType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options): void
        {
            $builder
                // ...
                ->add('photos', DropzoneType::class, [
                    'multiple' => true,
                ])
                // ...
            ;
        }

        // ...
    }

In ``multiple`` mode, the Dropzone accumulates files across successive
selections instead of replacing the previous one, displays a preview for
each selected file and lets the user remove files individually before
submitting.

Both modes dispatch the same events.
``dropzone:change`` is dispatched when files are added: beware that it carries a single ``File`` in single-file mode, but a ``FileList`` in ``multiple`` mode.
In ``multiple`` mode, ``dropzone:remove`` is dispatched when a file is removed, with the removed ``File`` in ``event.detail``.
``dropzone:clear`` is dispatched whenever the Dropzone becomes empty: when the file is cleared in single-file mode, when the last file is removed in ``multiple`` mode, and when the form is reset.
When the controller connects, for example after the element was moved in the page, both modes keep the files already in the input.

.. versionadded:: 3.7

    Before Dropzone 3.7, ``dropzone:clear`` was not dispatched in ``multiple`` mode, and in single-file mode the controller emptied the input and dispatched ``dropzone:clear`` when it connected.

The label of the per-file remove button can be changed with the
``remove_label`` option (default ``'Remove'``). It goes through the
form's translation domain, and the accessible name of each button is
that label followed by the file name::

    ->add('photos', DropzoneType::class, [
        'multiple' => true,
        'remove_label' => 'Delete',
    ])

Customizing the preview of each file
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 3.7

    The ``dropzone_preview_item_template`` block was added in Dropzone 3.7.

The preview is built with fixed markup and CSS classes: one list item per file in ``multiple`` mode, and the preview elements of the form theme in single-file mode.
To use your own markup (utility classes, an icon in the remove button, extra elements), override the ``dropzone_preview_item_template`` block in your form theme and render a ``<template>`` in it:

.. code-block:: html+twig

    {# templates/form/dropzone_theme.html.twig #}
    {% block dropzone_preview_item_template %}
        <template data-symfony--ux-dropzone--dropzone-target="previewItemTemplate">
            <li class="relative size-20">
                <div data-dropzone-slot="image" class="size-full bg-cover bg-center"></div>
                <span data-dropzone-slot="filename" class="sr-only"></span>
                <button type="button" data-dropzone-slot="remove" class="absolute top-1 right-1">
                    {{ ux_icon('lucide:x') }}
                </button>
            </li>
        </template>
    {% endblock %}

The template must contain exactly one root element, which is cloned for each file: otherwise, the controller does not connect and reports an error in the console.
The controller fills the elements marked with a ``data-dropzone-slot`` attribute, the root element included.
All slots are optional, and a slot can be on several elements:

* ``image``: shows the preview of an image file. On an ``<img>``, it is set as the ``src``, on any other element as the background image. The element has the ``hidden`` attribute until the preview is loaded, and keeps it for files that are not images;
* ``filename``: receives the name of the file, as text;
* ``remove``: removes the file on click. Its ``aria-label`` is the ``remove_label`` option followed by the file name.

In single-file mode, the copy of the template replaces the content of the preview, and its ``remove`` slot clears the Dropzone.

.. note::

    A class that sets ``display`` on the ``image`` slot (such as ``block`` or ``flex``) wins over its ``hidden`` attribute, so a file that is not an image shows an empty or broken image.
    Do not set ``display`` on this element, or add ``[data-dropzone-slot="image"][hidden] { display: none !important; }`` to your stylesheet.

Then use this form theme, for all forms or for a single one (see `How to Work with Form Themes`_).

Restricting the files
~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 3.7

    The filtering of dropped files by ``accept``, the ``max_files`` option and the ``dropzone:reject`` event were added in Dropzone 3.7.

The file picker only shows the files that match the ``accept`` attribute of the input, but drag and drop ignores it.
The Dropzone checks the dropped files against ``accept`` and leaves out the files that do not match.
An extension (``.pdf``) is checked against the file name, a MIME type (``image/png``, ``image/*``) against the type of the file.
The type comes from the operating system, so it can differ from the one you expect (on Windows, a CSV file is often ``application/vnd.ms-excel``): prefer extensions in ``accept``.
Browsers report no type for some files (HEIC photos, for example): those files are not rejected because of a MIME type.
In single-file mode, a rejected drop keeps the file selected before.

In ``multiple`` mode, the ``max_files`` option limits the number of files, picked or dropped.
When a selection goes over the limit, the files that fit are added and the others are left out.
When the Validator component is installed, the option also adds a ``Count`` constraint, so the limit is checked on the server too::

    ->add('photos', DropzoneType::class, [
        'multiple' => true,
        'max_files' => 5,
        'attr' => ['accept' => 'image/*'],
    ])

The files left out are not added, and the ``dropzone:reject`` event is dispatched once per reason, with the files in ``event.detail.files`` and the reason (``accept`` or ``max_files``) in ``event.detail.reason``.
In single-file mode, a selection of several files keeps the first one and leaves out the others with the ``max_files`` reason.
Use it to show your own message:

.. code-block:: javascript

    this.element.addEventListener('dropzone:reject', (event) => {
        const names = event.detail.files.map((file) => file.name).join(', ');
        alert(event.detail.reason === 'accept' ? `Not an image: ${names}` : `Too many files: ${names}`);
    });

The ``accept`` check only helps the user: validate the type of the files on the server too, for example with the ``File`` constraint::

    use Symfony\Component\Validator\Constraints\All;
    use Symfony\Component\Validator\Constraints\File;

    ->add('photos', DropzoneType::class, [
        'multiple' => true,
        'max_files' => 5,
        'attr' => ['accept' => 'image/*'],
        'constraints' => [
            new All([new File(mimeTypes: ['image/*'])]),
        ],
    ])

Customizing the design
~~~~~~~~~~~~~~~~~~~~~~

Symfony UX Dropzone provides a default stylesheet in order to ease
usage. You can disable it to add your own design if you wish.

In ``assets/controllers.json``, disable the default stylesheet by
switching the ``@symfony/ux-dropzone/dist/style.min.css`` autoimport to
``false``:

.. code-block:: json

    {
        "controllers": {
            "@symfony/ux-dropzone": {
                "dropzone": {
                    "enabled": true,
                    "fetch": "eager",
                    "autoimport": {
                        "@symfony/ux-dropzone/dist/style.min.css": false
                    }
                }
            }
        },
        "entrypoints": []
    }

.. note::

   *Note*: you should put the value to ``false`` and not remove the line
   so that Symfony Flex won't try to add the line again in the future.

Once done, the default stylesheet won't be used anymore and you can
implement your own CSS on top of the Dropzone.

.. versionadded:: 3.7

    The ``data-state`` attribute and the use of the ``hidden`` attribute were added in Dropzone 3.7.
    Before, the parts of the Dropzone were shown and hidden with inline styles.

The controller shows and hides the parts of the Dropzone (input, placeholder, preview, image preview) with the ``hidden`` attribute.
A rule of your own that sets ``display`` on these parts wins over the ``hidden`` attribute, so add this rule to your stylesheet:

.. code-block:: css

    .dropzone-container [hidden] {
        display: none !important;
    }

The container also has a ``data-state`` attribute, so you can style it for each state:

* ``empty``: no file is selected;
* ``filled``: at least one file is selected;
* ``dragging``: files are dragged over the Dropzone;
* ``processing``: a ``dropzone:pre-add`` listener is transforming files.

When several states apply, the last one of this list wins:

.. code-block:: css

    .dropzone-container[data-state="dragging"] {
        border-color: #3b82f6;
    }

    .dropzone-container[data-state="processing"] {
        opacity: 0.5;
    }

If you override the ``dropzone_widget`` block in your form theme, the clear button, the file name and the image preview of the single-file mode are optional: leave out the elements with the ``previewClearButton``, ``previewFilename`` or ``previewImage`` target that your design does not need.

Without the clear button, the user cannot deselect the file: once a file is chosen, the input is hidden behind the preview.
To clear the Dropzone from another element inside it, use the ``clear`` action of the controller:

.. code-block:: html

    <button type="button" data-action="symfony--ux-dropzone--dropzone#clear">Remove</button>

.. versionadded:: 3.7

    These targets became optional in Dropzone 3.7.

Extend the default behavior
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Symfony UX Dropzone allows you to extend its default behavior using a
custom Stimulus controller:

.. code-block:: javascript

    // mydropzone_controller.js

    import { Controller } from '@hotwired/stimulus';

    export default class extends Controller {
        connect() {
            this.element.addEventListener('dropzone:connect', this._onConnect);
            this.element.addEventListener('dropzone:change', this._onChange);
            this.element.addEventListener('dropzone:clear', this._onClear);
        }

        disconnect() {
            // You should always remove listeners when the controller is disconnected to avoid side-effects
            this.element.removeEventListener('dropzone:connect', this._onConnect);
            this.element.removeEventListener('dropzone:change', this._onChange);
            this.element.removeEventListener('dropzone:clear', this._onClear);
        }

        _onConnect(event) {
            // The dropzone was just created
        }

        _onChange(event) {
            // The dropzone just changed
        }

        _onClear(event) {
            // The dropzone has just been cleared
        }
    }

Then in your form, add your controller as an HTML attribute::

    // ...
    use Symfony\UX\Dropzone\Form\DropzoneType;

    class CommentFormType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options): void
        {
            $builder
                // ...
                ->add('photo', DropzoneType::class, [
                    'attr' => ['data-controller' => 'mydropzone'],
                ])
                // ...
            ;
        }

        // ...
    }

Transforming files before they are added
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. versionadded:: 3.7

    The ``dropzone:pre-add`` event was added in Dropzone 3.7.

Each time the user picks or drops files, the ``dropzone:pre-add`` event is dispatched before the files are added and previewed, in single-file and in ``multiple`` mode.
``event.detail.files`` holds the new files, as an array of ``File``.
A listener can:

* pass a promise to ``event.detail.waitUntil()``: the ``File`` objects the promise resolves to are added instead (fewer, more or other files). Call it once, synchronously, from the listener. It also accepts the array of files itself;
* call ``event.preventDefault()``: no file is added.

While a promise is pending, the container has a ``data-state="processing"`` attribute.
Use it to show a spinner or to disable the submit button: until the promise resolves, the input holds the files added before.
If the promise rejects, or resolves to something else than an array of ``File`` objects, the original files are added and the error is logged to the console.
Selections are added in the order they were made, and clearing the Dropzone drops the selections still processed.

In ``multiple`` mode, a file picked twice is added once: files are compared by name, size and ``lastModified``.
When you create a new ``File``, pass the ``lastModified`` of the original, or the same file picked twice is added twice.

For example, to resize the photos in the browser before they are uploaded:

.. code-block:: javascript

    // assets/controllers/image_resize_controller.js
    import { Controller } from '@hotwired/stimulus';

    const MAX_DIMENSION = 2000;

    export default class extends Controller {
        connect() {
            this.element.addEventListener('dropzone:pre-add', this._onPreAdd);
        }

        disconnect() {
            this.element.removeEventListener('dropzone:pre-add', this._onPreAdd);
        }

        _onPreAdd = (event) => {
            // Keep the original of a file that cannot be resized, without giving up on the others
            event.detail.waitUntil(Promise.all(event.detail.files.map((file) => this._resize(file).catch(() => file))));
        };

        async _resize(file) {
            if (!file.type.startsWith('image/')) {
                return file;
            }

            const bitmap = await createImageBitmap(file);
            try {
                const scale = Math.min(1, MAX_DIMENSION / Math.max(bitmap.width, bitmap.height));
                const canvas = new OffscreenCanvas(Math.round(bitmap.width * scale), Math.round(bitmap.height * scale));
                canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
                const blob = await canvas.convertToBlob({ type: 'image/jpeg', quality: 0.85 });

                return new File([blob], file.name.replace(/\.\w+$/, '.jpg'), { type: 'image/jpeg', lastModified: file.lastModified });
            } finally {
                bitmap.close();
            }
        }
    }

Then add the controller to the field::

    ->add('photos', DropzoneType::class, [
        'multiple' => true,
        'attr' => ['data-controller' => 'image-resize'],
    ])

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

.. _`the Symfony UX initiative`: https://ux.symfony.com/
.. _StimulusBundle configured in your app: https://symfony.com/bundles/StimulusBundle/current/index.html
.. _`@symfony/ux-dropzone npm package`: https://www.npmjs.com/package/@symfony/ux-dropzone
.. _`How to Work with Form Themes`: https://symfony.com/doc/current/form/form_themes.html
