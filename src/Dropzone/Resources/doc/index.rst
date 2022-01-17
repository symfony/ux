Symfony UX Dropzone
===================

Symfony UX Dropzone is a Symfony bundle providing light dropzones for
file inputs in Symfony Forms. It is part of `the Symfony UX initiative`_.

It allows visitors to drag and drop files into a container instead of
having to browse their computer for a file.

Installation
------------

Before you start, make sure you have `Symfony UX configured in your app`_.

Then, install this bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-dropzone

    # Don't forget to install the JavaScript dependencies as well and compile
    $ yarn install --force
    $ yarn encore dev

Also make sure you have at least version 3.0 of
`@symfony/stimulus-bridge`_ in your ``package.json`` file.

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
switching the ``@symfony/ux-dropzone/src/style.css`` autoimport to
``false``:

.. code-block:: json

    {
        "controllers": {
            "@symfony/ux-dropzone": {
                "dropzone": {
                    "enabled": true,
                    "fetch": "eager",
                    "autoimport": {
                        "@symfony/ux-dropzone/src/style.css": false
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

However it is currently considered `experimental`_, meaning it is not
bound to Symfony’s BC policy for the moment.

.. _`the Symfony UX initiative`: https://symfony.com/ux
.. _`@symfony/stimulus-bridge`: https://github.com/symfony/stimulus-bridge
.. _`experimental`: https://symfony.com/doc/current/contributing/code/experimental.html
.. _`Symfony UX configured in your app`: https://symfony.com/doc/current/frontend/ux.html
