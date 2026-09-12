Symfony UX Inspector
====================

Installation
------------

Requires PHP 8.4+, Symfony 7.4 or 8.x, and Chrome 145+ or Firefox 146+.

.. caution::

    This bundle is experimental. Register it only in ``dev``.

.. code-block:: terminal

    $ composer require --dev symfony/ux-inspector

The Flex recipe is not available yet. Register the bundle::

    // config/bundles.php
    return [
        // ...
        Symfony\UX\Inspector\UXInspectorBundle::class => ['dev' => true],
    ];

Import its asset route:

.. code-block:: yaml

    # config/routes/ux_inspector.yaml
    when@dev:
        ux_inspector:
            resource: '@UXInspectorBundle/config/routes.php'

The bundle loads its assets automatically.

.. _ux-inspector-stimulus-runtime:

Connect Stimulus (Optional)
~~~~~~~~~~~~~~~~~~~~~~~~~~~

By default, Stimulus inspection reads HTML attributes (``DOM only``).
Connecting the application lets the Inspector:

* Identify inactive controllers.
* Show default values for declared Stimulus values.
* Detect missing declared targets, CSS class attributes, and outlets.
* Check that methods referenced by actions exist.

In ``assets/bootstrap.js``, add these lines after ``startStimulusApp()``:

.. code-block:: diff

     // assets/bootstrap.js
     import { startStimulusApp } from '@symfony/stimulus-bundle';

     const app = startStimulusApp();
    +if (app.debug) {
    +    window.Stimulus = app;
    +}

With Encore, keep your existing ``startStimulusApp(...)`` call and add the same
lines. Use your application's variable name if it differs from ``app``.

List Components
---------------

Click the small pull tab on the right edge of the screen to open the panel.
It expands to show **UX** when the pointer approaches or the tab receives focus.
On touch devices, **UX** is visible without hovering. The tab hides while the
panel is open and returns when you close it.

You can also type ``ux`` outside an editable field to open the panel.

* **Components**: list detected components.
* **Find a component**: search by name, element tag, or ID.
* **Framework filters**: show Stimulus, LiveComponent, or Turbo components.
* **Show all components**: highlight components on the page.
* **Hide inspector**: close the panel.

Inspect Components
------------------

Click a component in the list, or use **Inspect page components** and click it
on the page. Hold ``Shift`` while clicking to keep inspection mode active.

Details update as the page changes. Follow element references to locate targets
and actions, or component links to inspect related components.
Use the breadcrumbs or ``Escape`` to go back.

Stimulus
~~~~~~~~

.. list-table::
    :header-rows: 1

    * - Data
      - Details
    * - Values and classes
      - HTML attribute values and, when connected, default values and missing
        CSS class attributes.
    * - Targets and actions
      - Elements and event bindings; checks for missing targets and action
        methods when connected.
    * - Relationships
      - Outlets, parent and child controllers.
    * - Activity
      - Controller-prefixed custom events from ``data-action`` and a predefined
        list of dispatch names, such as ``change`` in ``search:change``.

Stimulus ``connect()`` and ``disconnect()`` callbacks are not DOM events.
Other custom events may not be captured.

LiveComponent
~~~~~~~~~~~~~

.. list-table::
    :header-rows: 1

    * - Data
      - Details
    * - Props and models
      - LiveProps, props from parents, model bindings and modifiers.
    * - Actions and listeners
      - Action methods, arguments, event listeners, and related elements.
    * - Configuration and relationships
      - Loading directives, polling interval, parent and child components.
    * - Activity
      - Request actions, changed model names, render lifecycle, and errors.

LiveComponent activity works without connecting Stimulus.

Turbo
~~~~~

.. list-table::
    :header-rows: 1

    * - Data
      - Details
    * - Frames
      - ID, source URL, loading strategy, target, state, and nested frames.
    * - Page rules
      - Permanent elements, disabled scopes, frame targets, and Stream sources.
    * - Activity
      - Fetch method, URL, status and duration; frame renders, missing frames,
        morphs, and Stream actions with their targets.

Trace Activity
--------------

* **Show activity**: open the event log for the page.
* **Activity** in a component detail: show only this component's events.
* **Framework filters** and **Filter activity**: filter events.
* **All activity**: show events from all components.
* **Event row**: view event details. Follow the component link to inspect it.
* **Copy activity**: copy event details.

Consecutive matching events are grouped. Completed Turbo request/response pairs
appear as one fetch operation.

Configuration
-------------

Injection requires an HTML response with explicit opening and closing ``body``
tags. A ``head`` element is optional. The Symfony Web Debug Toolbar
(``.sf-toolbar``) is automatically excluded from component detection and page
inspection; its internal DOM mutations do not trigger Inspector updates.

Default values:

.. code-block:: yaml

    # config/packages/ux_inspector.yaml
    when@dev:
        ux_inspector:
            enabled: true
            pull_tab: true
            exclude_paths: ['/_profiler', '/_wdt', '/_error', '/_fragment']
            ignore_selectors: []

* ``enabled``: enable the Inspector only when ``kernel.debug`` is also true.
* ``pull_tab``: display the pull tab on the right edge of the screen. Set to
  ``false`` to hide it; the ``ux`` shortcut remains available.
* ``exclude_paths``: hide the Inspector on matching paths. ``/admin`` matches
  ``/admin/users``, but not ``/administrator``. Replaces the default list.
* ``ignore_selectors``: skip matching elements and their descendants when
  detecting components, e.g. ``['[data-no-inspect]']``.
