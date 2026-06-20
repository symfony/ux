EditorJS Bridge
===============

`symfony/ux-editor-editorjs`_ integrates `EditorJS`_ with ``symfony/ux-editor``.

Installation
------------

.. code-block:: terminal

    $ composer require symfony/ux-editor-editorjs

Then add the upstream EditorJS library. With AssetMapper:

.. code-block:: terminal

    $ php bin/console importmap:require @editorjs/editorjs

Or with Webpack Encore:

.. code-block:: terminal

    $ npm install @editorjs/editorjs

Optional EditorJS tool packages (applications install the tools they need and register classes on
``window.UXEditorJSTools`` so the controller can resolve them):

.. code-block:: terminal

    $ npm install @editorjs/header @editorjs/list @editorjs/image @editorjs/quote

Use
---

::

    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\FormBuilderInterface;
    use Symfony\UX\Editor\Bridge\EditorJS\Config\EditorJSConfig;
    use Symfony\UX\Editor\Bridge\EditorJS\Config\ToolDefinition;
    use Symfony\UX\Editor\Config\CommonOptions;
    use Symfony\UX\Editor\Form\EditorType;

    final class ArticleType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $b, array $options): void
        {
            $b->add('body', EditorType::class, [
                'config' => new EditorJSConfig(
                    common: new CommonOptions(placeholder: 'Tell your story…'),
                    tools: [
                        'header'    => new ToolDefinition('Header',    ['levels' => [2, 3, 4]]),
                        'paragraph' => new ToolDefinition('Paragraph', ['preserveBlank' => true]),
                        'list'      => new ToolDefinition('List'),
                        'image'     => new ToolDefinition('Image',     ['endpoints' => ['byFile' => '/_ux_editor/upload/body']]),
                        'quote'     => new ToolDefinition('Quote'),
                    ],
                ),
            ]);
        }
    }

Or use the shipped preset:

::

    $b->add('body', EditorType::class, ['preset' => 'blog.standard']);

Render saved content
--------------------

.. code-block:: twig

    {{ ux_editor_render(article.body) }}

Five block renderers ship with the bridge (paragraph, header, list, image, quote). Override
or add renderers by tagging your service with ``ux.editor.block_renderer``.

Tool registration on the JS side
--------------------------------

The bridge controller resolves tool class names listed in ``EditorJSConfig::tools`` against
``window.UXEditorJSTools``. Register tools in your app entrypoint:

.. code-block:: javascript

    import Header from '@editorjs/header';
    import List   from '@editorjs/list';
    import Image  from '@editorjs/image';
    import Quote  from '@editorjs/quote';

    window.UXEditorJSTools = { Header, List, Image, Quote };

Unregistered tool names are skipped with a console warning.

.. _`symfony/ux-editor-editorjs`: https://github.com/symfony/ux
.. _`EditorJS`: https://editorjs.io
