Symfony UX Image documentation
==============================

An uploaded file is not yet a responsive image contract. Applications still have to
answer:

- Which file is the durable original?
- Where do crop, focal-point, format and responsive rules live?
- What can safely be persisted when a storage or CDN URL changes?
- Can Twig render correct markup without opening the file again?
- How are codecs, pixel budgets, regeneration and cleanup tested?

UX Image gives those questions one explicit pipeline. It processes a raster ``UploadedFile`` through a named profile, stores the original and variants, returns a versioned ``ImageAsset``, then renders HTML from that metadata alone.

The application still owns the upload UI, authorization, entity lifecycle and business
cleanup policy.

Mental model
------------

.. code-block:: text

                        upload time
    UploadedFile
        │ inspect real bytes and enforce limits
        ▼
    named profile ── variant geometry ── output formats
        │
        ▼
    storage writes original + generated files
        │
        ▼
    ImageAsset { storageName, paths, dimensions, variants, profile revision }
        │
        │ persist with the owning model
        │
        ▼               render time: no storage I/O
    ux_picture() / ux_image() / <twig:ux:image>
        │
        ▼
    <picture> + <source> + <img>

Each boundary has one job:

========  ===========================  ===========================  ===========================================
Boundary  Input                        Output                       Owns
========  ===========================  ===========================  ===========================================
Inspect   ``UploadedFile``             trusted dimensions and MIME  byte signature and processing limits
Profile   inspected source             planned variants             geometry, quality, formats, focal point
Publish   original + encoded variants  ``ImageAsset``               storage paths and all-or-nothing writes
Render    persisted ``ImageAsset``     responsive HTML              URLs, source order, fallback and attributes
========  ===========================  ===========================  ===========================================

Choose the first contract
-------------------------

Start from the layout decision, not from a codec:

=================================  =================================================  ====================================================
Need                               Profile choice                                     Why
=================================  =================================================  ====================================================
Fluid content image                width variants with ``fit``                        preserves the complete source
Stable avatar or thumbnail         fixed width/height with ``crop`` and ``position``  keeps the subject inside an exact ratio
Fixed canvas without cropping      fixed width/height with ``fill``                   letterboxes the complete source
Different composition by viewport  variants with ``media``                            emits art-directed ``<source>`` elements
Portable first deployment          ``formats: [jpeg]``                                works with every supported GD installation
Modern delivery                    AVIF/WebP plus JPEG                                negotiates smaller formats with a universal fallback
=================================  =================================================  ====================================================

Run ``php bin/console ux:image:validate`` before enabling a codec in production. The command checks the selected driver's real capabilities; UX Image never silently writes another binary format under the requested extension.

Follow one image
----------------

1. :doc:`Install and process the first image <installation>`.
2. :doc:`Define profiles, variants, focal points and formats <processing>`.
3. :doc:`Inspect the exact Twig and generated HTML <rendering>`.
4. :doc:`Choose local, Flysystem or custom storage <storage>`.
5. :doc:`Persist the versioned ImageAsset <image-asset>`.

For production:

- :doc:`regenerate variants and integrate asynchronous processing <regeneration>`;
- :doc:`compose with Forms, UX Upload, Doctrine, Messenger and themes <integrations>`;
- :doc:`debug configuration, codecs, URLs and memory <debugging>`;
- :doc:`test with deterministic assets, mocks and real temporary storage <testing>`;
- :doc:`set security limits and review the production checklist <security>`;
- :doc:`inspect every available option <configuration>`;
- :doc:`understand internal classes and extension points <architecture>`.

Three rules to remember
-----------------------

1. Persist the ``ImageAsset`` returned by ``process()``; never reconstruct variant paths in application code.
2. Treat ``storageName + path`` as durable identity. Public URLs are derived and may change with storage or CDN configuration.
3. Keep authorization and owner cleanup in the application. UX Image owns deterministic
   image processing, not the entity or upload lifecycle.
