Customizing The Upload Field
============================

UX Upload separates behavior, markup and presentation:

- ``FileUploadType`` options select capabilities and a semantic layout;
- the bundle form theme defines all markup through composable Twig blocks;
- Stimulus updates state, text and accessibility attributes;
- optional stylesheets provide ready-to-use visual treatments.

No stylesheet is loaded automatically.

Choose A Customization
----------------------

Start with the result you want:

.. list-table::
    :header-rows: 1
    :widths: 40 60

    * - If you want to...
      - Do this
    * - Keep your application's existing form appearance
      - Keep the default ``compact`` layout and load no UX Upload stylesheet.
        Standard labels, help, errors and row attributes come from the active
        Symfony form theme.
    * - Get a ready-to-use upload field
      - Keep ``'layout' => 'compact'`` and load
        ``@symfony/ux-upload/dist/compact.min.css``.
    * - Build a drop area or image gallery
      - Set ``'layout' => 'dropzone'``, optionally enable ``show_preview`` and
        ``multiple``, then load ``@symfony/ux-upload/dist/dropzone.min.css``.
    * - Integrate an application design system
      - Load no package stylesheet, import the bundle form theme with Twig
        ``use``, override only the necessary blocks and preserve the
        :ref:`required behavior hooks <ux-upload-behavior-hooks>`.

Layouts And Previews
--------------------

``layout`` selects the structure to emphasize. ``show_preview`` independently
controls whether image previews are rendered. Both layouts support single and
multiple files.

::

    $builder->add('attachments', FileUploadType::class, [
        'layout' => 'dropzone', // or "compact"
        'show_preview' => true,
        'multiple' => true,
        'max_files' => 6,
    ]);

The default layout is ``compact``. Its public Symfony field ID belongs to the
real file input, so the normal ``form_row()`` label and application form theme
continue to work. Without package CSS, both layouts retain a visible native
file input and remain usable.

With ``layout: dropzone`` and ``show_preview: true``, the picker joins the
preview grid after the first file. It disappears when ``max_files`` is reached
and returns when an item is removed. Twig owns this structure; the controller
only updates upload state.

Optional Styles
---------------

UX Upload publishes two standalone Baseline 2026 stylesheets:

================  ==================================================
Layout            AssetMapper path
================  ==================================================
Compact field     ``@symfony/ux-upload/dist/compact.min.css``
Drop area         ``@symfony/ux-upload/dist/dropzone.min.css``
================  ==================================================

Enable one explicitly in ``assets/controllers.json``:

.. code-block:: json

    {
        "controllers": {
            "@symfony/ux-upload": {
                "upload": {
                    "enabled": true,
                    "fetch": "eager",
                    "autoimport": {
                        "@symfony/ux-upload/dist/compact.min.css": true,
                        "@symfony/ux-upload/dist/dropzone.min.css": false
                    }
                }
            }
        }
    }

Keep both entries and switch their boolean values when changing the global
style. To load a style only on selected pages, leave both values ``false`` and
use AssetMapper directly:

.. code-block:: html+twig

    <link rel="stylesheet" href="{{ asset('@symfony/ux-upload/dist/dropzone.min.css') }}">

The npm package also exports ``@symfony/ux-upload/compact.css`` and
``@symfony/ux-upload/dropzone.css``. Importing neither is supported.

.. _ux-upload-css-custom-properties:

CSS Custom Properties
---------------------

Override the public properties on the field or an application wrapper:

.. code-block:: css

    .document-upload {
        --ux-upload-accent: #0f766e;
        --ux-upload-accent-strong: #115e59;
        --ux-upload-bg: #ffffff;
        --ux-upload-bg-muted: #f4f4f5;
        --ux-upload-text: #18181b;
        --ux-upload-muted: #71717a;
        --ux-upload-border: #d4d4d8;
        --ux-upload-danger: #b91c1c;
        --ux-upload-success: #047857;
        --ux-upload-radius: 0.5rem;
        --ux-upload-gap: 0.75rem;
    }

The published styles use ``light-dark()`` and follow the surrounding
``color-scheme``. Applications may override every property for either mode.

Browser Support
~~~~~~~~~~~~~~~

The optional stylesheets target Baseline 2026. They use cascade layers,
``light-dark()``, ``color-mix()`` and ``:has()``. These features affect only
the ready-to-use presentation. The field keeps a visible native file input and
remains usable when the stylesheets are not loaded or when an application
provides its own CSS for older browsers.

Overriding Twig Markup
----------------------

TwigBundle automatically receives ``@UXUpload/form_theme.html.twig``. It
contains the complete markup. Its ten public blocks form one composable form
theme contract, and the main ``ux_upload_widget`` block composes the smaller
blocks. They are not ten unrelated extension APIs. A custom application theme
normally imports the contract once and overrides one or several related blocks
in the same file.

===========================  =============================================
Block                        Responsibility
===========================  =============================================
``ux_upload_row``            Standard Symfony Form row integration
``ux_upload_widget``         Complete field and required behavior hooks
``ux_upload_picker``         Native picker and drop area
``ux_upload_item``           Repeated upload item template
``ux_upload_visual``         Preview and file icon
``ux_upload_progress``       Per-file progress
``ux_upload_actions``        Pause, resume, cancel, remove and retry actions
``ux_upload_summary``        Aggregate progress
``ux_upload_client_errors``  Client upload error container and template
``ux_upload_start``          Manual upload action
===========================  =============================================

.. _ux-upload-application-form-theme:

Application Form Theme
~~~~~~~~~~~~~~~~~~~~~~

Override a block in an application form theme when the change belongs to one
form, one page or the application's form system. Import the complete UX Upload
contract, replace only the block you own, and use ``parent()`` to preserve its
default markup when needed:

.. code-block:: html+twig

    {# templates/form/upload_theme.html.twig #}
    {% use '@UXUpload/form_theme.html.twig' %}

    {% block ux_upload_actions %}
        <div class="document-upload__actions">
            {{ parent() }}
        </div>
    {% endblock %}

Apply it with the standard Symfony Form syntax:

.. code-block:: twig

    {# templates/document/new.html.twig #}
    {% form_theme form 'form/upload_theme.html.twig' %}

    {{ form_row(form.attachments) }}

This is the recommended extension point for contextual rendering. All other UX
Upload blocks continue to use their defaults.

.. _ux-upload-global-form-theme:

Global Application Form Theme
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Register the same application form theme globally when the customization must
apply to every UX Upload field:

.. code-block:: yaml

    # config/packages/twig.yaml
    twig:
        form_themes:
            - 'form/upload_theme.html.twig'

Use ``{% use %}``, not ``{% extends %}``, in a custom form theme. Twig imports
the complete block contract like a trait, lets the application replace one
block and makes the imported implementation available through ``parent()``.

Required And Optional Markup
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The ``ux_upload_widget``, ``ux_upload_picker`` and ``ux_upload_item`` defaults
contain the required controller, input, result, list and item hooks. Preserve
these hooks by calling ``parent()`` or by starting from the original block when
replacing one of them.

The visual, progress, actions, summary and client errors blocks are optional.
They may be empty without preventing a successful upload. The controller checks
for their elements before updating them. The announcement region remains
internal to the widget because removing it would silently reduce accessibility.

Native And Widget Attributes
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Use the standard Symfony Form ``attr`` option for the native file input and
``widget_attr`` for the UX Upload container::

    $builder->add('attachments', FileUploadType::class, [
        'attr' => [
            'class' => 'document-file-input',
            'capture' => 'environment',
        ],
        'widget_attr' => [
            'class' => 'document-upload',
            'data-controller' => 'document-form',
        ],
    ]);

The native input receives ``attr``, including the accessibility attributes
generated by ``form_row()``. Its ``type``, ``id``, ``name``, ``value``,
``multiple``, ``required`` and ``disabled`` attributes remain controlled by UX
Upload. In particular, the native input never receives a name: only the hidden
token field participates in form submission.

The widget container receives ``widget_attr`` in addition to the required UX
Upload controller, values and classes. Additional Stimulus controllers are
merged into the same ``data-controller`` attribute. Twig renders both sets of
attributes through the standard Symfony Form ``attributes`` block, including
its escaping and boolean semantics.

``row_attr``, ``label``, ``label_attr``, ``help`` and ``help_attr`` retain their
standard Symfony Form behavior. Transformation and validation errors are
rendered by the active application form theme and associated with the native
input through ``aria-invalid`` and ``aria-describedby``.

Error Placement
~~~~~~~~~~~~~~~

UX Upload has two error levels:

- an error attributable to one file is displayed in that file's upload item;
- a field-level error is displayed with the form field. Symfony transformation
  and validation errors use the active form theme, while client feedback that
  cannot be attributed to one file uses ``ux_upload_client_errors``.

These are two presentation levels even though field-level messages can come
from either Symfony or the browser controller.

.. _ux-upload-behavior-hooks:

Behavior Hooks
--------------

CSS classes are presentation-only and may be renamed. Custom blocks must
preserve the Stimulus controller, actions and targets from the original block.
A custom item block must also preserve these attributes on the corresponding
elements:

==============================  =====================================
Attribute                       Purpose
==============================  =====================================
``data-ux-upload-item``         Repeated item root
``data-ux-upload-name``         Original filename
``data-ux-upload-size``         Formatted size
``data-ux-upload-status``       Status and transfer details
``data-ux-upload-progress``     Accessible progressbar
``data-ux-upload-progress-bar`` Visual progress fill
``data-ux-upload-percent``      Numeric progress
``data-ux-upload-preview``      Optional image supplied by Twig
``data-ux-upload-file-icon``    Non-image fallback supplied by Twig
``data-ux-upload-action``       Action whose visibility and availability follow state
==============================  =====================================

Stimulus clones Twig ``<template>`` elements for items and errors. It never
constructs presentation elements or injects HTML strings. Omitting an optional
preview or progress element is supported; the controller updates only elements
that exist. It also updates the native ``hidden`` and ``disabled`` properties
of actions, so their behavior does not depend on a package stylesheet.

View Variables
--------------

``FileUploadType::buildView()`` exposes:

===================  ===================================================
Variable             Meaning
===================  ===================================================
``layout``           ``compact`` or ``dropzone``
``show_preview``     Whether item templates contain image previews
``max_size``         Effective bytes limit
``max_files``        Effective file count
``allowed_types``    Effective MIME restrictions
``multiple``         Multiple-file mode
``auto_upload``      Automatic start
``compression``      Client compression
``uploader``         Named uploader
``stimulus_values``  URLs, security values, policy and translated labels
===================  ===================================================
