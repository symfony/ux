CKEditor 5 Bridge
=================

`symfony/ux-editor-ckeditor`_ integrates `CKEditor 5`_ with ``symfony/ux-editor``.

Installation
------------

.. code-block:: terminal

    $ composer require symfony/ux-editor-ckeditor

Then add the upstream CKEditor build. With AssetMapper:

.. code-block:: terminal

    $ php bin/console importmap:require @ckeditor/ckeditor5-build-classic

Or with Webpack Encore:

.. code-block:: terminal

    $ npm install @ckeditor/ckeditor5-build-classic

CKEditor 5 v44+ requires a license key. Use ``'GPL'`` for the open-source license:

.. code-block:: php

    new CKEditorConfig(licenseKey: 'GPL')

Use
---

.. code-block:: php

    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\FormBuilderInterface;
    use Symfony\UX\Editor\Bridge\CKEditor\Config\CKEditorConfig;
    use Symfony\UX\Editor\Config\CommonOptions;
    use Symfony\UX\Editor\Form\EditorType;

    final class ArticleType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $b, array $options): void
        {
            $b->add('body', EditorType::class, [
                'config' => new CKEditorConfig(
                    common: new CommonOptions(
                        toolbar: ['heading', 'bold', 'italic', 'link', 'bulletedList'],
                        placeholder: 'Write…',
                        language: 'fr',
                    ),
                    extraPlugins: ['SourceEditing'],
                    licenseKey: 'GPL',
                ),
            ]);
        }
    }

Or use a shipped preset:

.. code-block:: php

    $b->add('body', EditorType::class, ['preset' => 'wysiwyg.full']);

Two presets ship with the bridge: ``wysiwyg.minimal`` (bold/italic/link) and ``wysiwyg.full``
(heading, formatting, lists, blockquote, undo/redo, heading config).

Sanitization
------------

By default ``EditorType`` runs the configured ``HtmlSanitizer`` on submit. Disable per field
if your content must round-trip raw HTML:

.. code-block:: php

    $b->add('body', EditorType::class, ['config' => new CKEditorConfig(), 'sanitize' => false]);

Native overrides
----------------

For any CKEditor option not exposed by ``CKEditorConfig``, pass through via ``nativeOverrides``:

.. code-block:: php

    new CKEditorConfig(
        nativeOverrides: ['ui' => ['poweredBy' => ['forceVisible' => false]]],
    )

Native overrides are merged **last** — they always win.

.. _`symfony/ux-editor-ckeditor`: https://github.com/symfony/ux
.. _`CKEditor 5`: https://ckeditor.com/ckeditor-5/
