The CSS reset
=============

Browsers ship their own default styles: margins on the ``body``, bold
headings, bullets on lists, a different font in form controls. The
``preflight`` option writes a CSS reset that removes them, so that ``css()``
starts from the same page in every browser. It is the ``preflight`` option of
`Panda CSS`_, and it writes the same CSS.

The reset is off by default. To turn it on:

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        preflight: true

The bundle then writes the reset at the top of ``var/ux_css/styles.css``, in
its own cascade layer named ``reset``:

.. code-block:: css

    @layer reset, tokens, utilities;

    @layer reset {
      html,:host {
        line-height: 1.5;
        font-family: var(--global-font-body, var(--font-fallback));
        /* ... */
      }

      *,::before,::after,::backdrop,::file-selector-button {
        margin: 0px;
        padding: 0px;
        box-sizing: border-box;
        /* ... */
      }

      /* ... */
    }

With the option off, the file has no ``reset`` layer and is the same as before.

What the reset does
-------------------

* it removes the margins, the paddings and the borders, and sets
  ``box-sizing: border-box`` on every element;
* it sets a line height, a system font stack and a text size adjustment on the
  page;
* headings take the font size and weight of their parent, and lists have no
  bullets;
* images, videos and other embedded content are blocks that never overflow
  their parent;
* form controls and buttons inherit the font, the color and the letter
  spacing of the page;
* links inherit the color and the text decoration of their parent;
* the ``hidden`` attribute hides the element, even when ``css()`` sets a
  ``display``.

Values go through the same engine as ``css()``. The reset does not read the
``strict_tokens`` and ``strict_property_values`` options, but a token replaces
a value that names one. With a ``transparent`` color token, for example, the
form controls use ``var(--colors-transparent)`` as their background.

Scoping the reset
-----------------

By default, the reset applies to the whole page. Use ``scope`` to limit it to
a part of the page, for example when it embeds a widget in a page that already
has its own styles:

.. code-block:: yaml

    # config/packages/ux_css.yaml
    ux_css:
        preflight:
            scope: '.reset'
            level: parent

``scope`` is a CSS selector. ``level`` is ``parent`` or ``element``, and
``parent`` when you do not set it:

``parent``
    The scope is the parent of the elements to reset: the reset applies to what
    is inside an element that matches it. ``.reset img`` is written for the
    images inside a ``.reset`` element.

``element``
    The scope is on the elements themselves: the reset applies to the elements
    that match it. ``img.reset`` is written for the images that have the
    ``reset`` class. The pseudo-elements stay under the scope:
    ``.reset ::selection``.

In both cases, the rule that sets the font and the line height of the page is
written for the scope itself, instead of ``html, :host``. ``level`` requires a
``scope``.

Setting the fonts and colors
----------------------------

The reset reads a few optional CSS variables, and falls back to a default value
when they are not set:

=============================== ============================================
Variable                        Used for
=============================== ============================================
``--global-font-body``          the font family of the page
``--global-font-mono``          the font family of ``code``, ``kbd``,
                                ``samp`` and ``pre``
``--global-color-border``       the color of the borders
``--global-color-placeholder``  the color of the placeholder of a form field
``--global-color-selection``    the background of the selected text
=============================== ============================================

Set them from your tokens, in a stylesheet outside of the ``reset`` layer:

.. code-block:: css

    /* assets/styles/app.css */
    :root {
        --global-font-body: var(--fonts-sans);
        --global-font-mono: var(--fonts-mono);
        --global-color-border: var(--colors-border);
    }

The tokens are CSS variables too, so the variables of the reset follow them,
including their values for ``_dark`` and the other conditions.

The layer order
---------------

The reset only wins over the browser's styles. Everything else must come after
it, so the ``reset`` layer is the first one. The generated file declares
``@layer reset, tokens, utilities;``, and your own stylesheet should declare
every layer it uses, in the order you want. See "The CSS layers" in
:doc:`css-function`.

.. _`Panda CSS`: https://panda-css.com/docs/reference/config#preflight
