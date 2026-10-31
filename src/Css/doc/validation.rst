Catching mistakes
=================

``css()`` checks every style it can, as early as it can. A mistake in a
template is a Twig syntax error, with the template name and the line, like a
misspelled filter.

What is checked
---------------

=========================================== ====================================
Mistake                                     Message
=========================================== ====================================
Unknown property                            ``Unknown property "colr". Did you
                                            mean "color"?``
Unknown condition                           ``Unknown condition "_hovr". Did
                                            you mean "_hover"?``
Unknown breakpoint                          ``Unknown property "mdd". Did you
                                            mean "md"?``
Unknown token                               ``Unknown colors token "primry".
                                            Did you mean "primary"?``
Raw value without brackets                  ``Unknown sizes token "37ch".
                                            Write a raw value between brackets,
                                            like "[37ch]".``
Category with no token                      ``Unknown fontSizes token "lg": no
                                            fontSizes token is declared. Add
                                            fontSizes tokens to the design
                                            tokens, under font.size, font-size,
                                            fontSize or fontSizes, or write a
                                            raw value between brackets, like
                                            "[lg]".``
Invalid keyword                             ``Invalid value "flexx" for
                                            "display". Did you mean "flex"?``
``base`` outside a conditional value        ``"base" can only be used inside a
                                            conditional value, like { color: {
                                            base: 'red', _hover: 'blue' } }.``
Forbidden character                         ``The value of "content" cannot
                                            contain ";", "{", "}", "\", quotes,
                                            "<" or a CSS comment, ""x"" given.``
Several hashes                              ``css() takes a single hash of
                                            styles.``
=========================================== ====================================

Unknown tokens, raw values without brackets, and bare strings for a category
with no token are refused by ``strict_tokens``. Invalid keywords are refused by
``strict_property_values``. Both options are enabled by default: see
:doc:`configuration` to turn them off.

Where mistakes show up
----------------------

The check runs when Twig compiles a template. That happens in three places:

* **In dev**, when you open a page: Twig recompiles the templates you changed,
  and the error page shows the line.
* **In CI**, with ``lint:twig``, which compiles every template:

  .. code-block:: terminal

      $ php bin/console lint:twig templates/

* **When building for production**: ``cache:warmup`` reads every template to
  write the CSS file, and fails on the first invalid ``css()`` call when the
  kernel is not in debug mode.

In dev, the bundle also reads every template when the cache is cold, to write
the CSS file. A template with a mistake is skipped there, so the application
still starts, and the error shows when you open a page that uses the
template.

Values only known at runtime
----------------------------

A value that comes from a variable cannot be checked when the template
compiles:

.. code-block:: html+twig

    <span class="{{ css({ color: status.color }) }}">

In debug mode, ``css()`` checks such a hash when the template renders, with
the same rules, and a mistake is a Twig runtime error. Outside of debug mode,
it only computes the class names, to stay fast. See :doc:`dynamic-styles`.

Configuration mistakes
----------------------

Mistakes in the bundle configuration, such as a reference to a token that
does not exist, are reported when the container is built, by ``cache:clear``
or the first request. :doc:`tokens` and :doc:`conditions` list them.
