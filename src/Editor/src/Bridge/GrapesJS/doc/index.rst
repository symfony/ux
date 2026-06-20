GrapesJS Bridge
===============

`symfony/ux-editor-grapesjs`_ integrates `GrapesJS`_ with ``symfony/ux-editor``.

Installation
------------

.. code-block:: terminal

    $ composer require symfony/ux-editor-grapesjs

Then add the upstream GrapesJS library. With AssetMapper:

.. code-block:: terminal

    $ php bin/console importmap:require grapesjs

Or with Webpack Encore:

.. code-block:: terminal

    $ npm install grapesjs

Use
---

::

    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\FormBuilderInterface;
    use Symfony\UX\Editor\Bridge\GrapesJS\Config\GrapesJSConfig;
    use Symfony\UX\Editor\Config\CommonOptions;
    use Symfony\UX\Editor\Form\EditorType;

    final class LandingPageType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $b, array $options): void
        {
            $b->add('homepage', EditorType::class, [
                'config' => new GrapesJSConfig(
                    common: new CommonOptions(language: 'en'),
                    blocks: [
                        ['id' => 'hero', 'label' => 'Hero', 'content' => '<section><h1>Hero</h1></section>'],
                    ],
                    canvasCss: 'body{font-family:system-ui;margin:0}',
                ),
            ]);
        }
    }

Or use the shipped preset:

::

    $b->add('homepage', EditorType::class, ['preset' => 'page_builder.landing']);

Rendering saved page content
----------------------------

``PageContent`` renders in a sandboxed iframe via ``ux_editor_render``:

.. code-block:: twig

    {{ ux_editor_render(page.homepage) }}

For embedding directly in your HTML (not iframed), use ``PageAssetExtractor`` shipped in
``symfony/ux-editor`` to collect asset URLs and reconcile them with your storage.

Native overrides
----------------

Any GrapesJS option not exposed by ``GrapesJSConfig`` passes through ``nativeOverrides``:

::

    new GrapesJSConfig(
        nativeOverrides: ['styleManager' => ['sectors' => [...]]],
    )

.. _`symfony/ux-editor-grapesjs`: https://github.com/symfony/ux
.. _`GrapesJS`: https://grapesjs.com
