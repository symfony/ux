Symfony UX Editor
=================

`symfony/ux-editor`_ provides one Symfony Form field, ``EditorType``, that integrates
multiple content-authoring editors (WYSIWYG, block, page builder) through a single API.

Installation
------------

.. code-block:: terminal

    $ composer require symfony/ux-editor

Then install a bridge package for the editor you want to use, for example:

.. code-block:: terminal

    $ composer require symfony/ux-editor-editorjs
    $ composer require symfony/ux-editor-ckeditor
    $ composer require symfony/ux-editor-grapesjs

Quick start
-----------

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
                    common: new CommonOptions(toolbar: ['bold','italic','link'], placeholder: 'Write…'),
                ),
            ]);
        }
    }

Architecture
------------

Three-tier stack:

* **Tier 0 — Core**: ``EditorType``, value objects (``HtmlContent`` / ``BlockContent`` / ``PageContent``),
  Doctrine custom types, upload pipeline, ``LiveEditor`` trait, Twig ``ux_editor_render`` function.
* **Tier 1 — Format abstracts**: ``AbstractWysiwygBridge`` / ``AbstractBlockBridge`` /
  ``AbstractPageBuilderBridge`` plus matching configs, transformers, controllers, capability factories.
  Shipped inside ``symfony/ux-editor``.
* **Tier 2 — Specific bridges**: CKEditor, Quill, TinyMCE, TipTap, EditorJS, BlockNote, GrapesJS, VvvebJS.
  Each ships as its own composer + npm sub-package.

See ``docs/superpowers/specs/2026-05-19-ux-editor-design.md`` in the source repository for the full design specification.

.. _`symfony/ux-editor`: https://github.com/symfony/ux
