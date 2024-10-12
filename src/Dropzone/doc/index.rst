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

Usage
-----

The most common usage of Symfony UX Dropzone is to use it as a drop-in
replacement of the native FileType class::

    // ...
    use Symfony\UX\Dropzone\Form\DropzoneType;

    class CommentFormType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options)
        {
            $builder
                // ...
                ->add('photo', DropzoneType::class)
                // ...
            ;
        }

        // ...
    }

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
   so that Symfony Flex won’t try to add the line again in the future.

Once done, the default stylesheet won’t be used anymore and you can
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
        public function buildForm(FormBuilderInterface $builder, array $options)
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
