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

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

.. _`the Symfony UX initiative`: https://ux.symfony.com/
.. _StimulusBundle configured in your app: https://symfony.com/bundles/StimulusBundle/current/index.html
.. _`@symfony/ux-dropzone npm package`: https://www.npmjs.com/package/@symfony/ux-dropzone
