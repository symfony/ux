How to Use Symfony UX Turbo with Reprise
========================================

Installation
------------

Reprise reads the Stimulus controllers registered in
``assets/controllers.json``, so once you point the `Symfony Reprise`_
plugin at that file, Turbo's controllers are picked up automatically.
See the StimulusBundle documentation for how to set that up.

After installing Symfony UX Turbo, install your assets and rebuild:

.. code-block:: terminal

    $ npm install --force
    $ npm run dev

Reloading When a JavaScript/CSS File Changes
--------------------------------------------

Turbo Drive can automatically perform a full refresh when a CSS or JS
file changes, so your users always get the latest version.

Unlike WebpackEncore, there's nothing to enable for versioning:
Reprise content-hashes your files by default.

All you need to do is add a ``data-turbo-track="reload"`` attribute to
your rendered ``script`` and ``link`` tags. Do it globally:

.. code-block:: yaml

    # config/packages/reprise.yaml
    reprise:
        # ...

        script_attributes:
            'data-turbo-track': reload
        link_attributes:
            'data-turbo-track': reload

You can also pass the same attribute per call, as a fourth
``attributes`` argument on ``reprise_entry_script_tags()`` and
``reprise_entry_link_tags()``:

.. code-block:: twig

    {{ reprise_entry_script_tags('app', attributes={ 'data-turbo-track': 'reload' }) }}

For more info, see: `Turbo Reloading When Assets Change`_.

.. _`Turbo Reloading When Assets Change`: https://turbo.hotwired.dev/handbook/drive#reloading-when-assets-change
.. _`Symfony Reprise`: https://github.com/symfony/reprise
