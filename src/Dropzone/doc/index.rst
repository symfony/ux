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

In this mode, the ``dropzone:connect``, ``dropzone:change`` and
``dropzone:remove`` events are dispatched (``dropzone:clear`` is not
dispatched, as there is no clear button in ``multiple`` mode). Beware
that ``dropzone:change`` carries a single ``File`` in single-file mode,
but a ``FileList`` in ``multiple`` mode. The ``dropzone:remove`` event
is dispatched when a file is removed, and carries the removed ``File``
in ``event.detail``.

The label of the per-file remove button can be changed with the
``remove_label`` option (default ``'Remove'``). It goes through the
form's translation domain, and the accessible name of each button is
that label followed by the file name::

    ->add('photos', DropzoneType::class, [
        'multiple' => true,
        'remove_label' => 'Delete',
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
Use it to show a spinner or to disable the submit button: until the promise resolves, the input holds the files selected before (in single-file mode, the original file).
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
