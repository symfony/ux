Symfony UX Image
================

Symfony UX Image processes uploaded raster images into versioned ``ImageAsset``
values, stores originals and responsive variants, and renders optimized
``<picture>`` markup.

The documentation is organized as a navigable, task-oriented corpus instead of
one monolithic page:

- :doc:`installation` covers installation and the first processed image.
- :doc:`processing` explains profiles, variants and formats.
- :doc:`rendering` covers responsive HTML and Twig integration.
- :doc:`storage` covers local, Flysystem and CDN delivery.
- :doc:`image-asset` defines the Doctrine and persistence contract.
- :doc:`regeneration` covers variant regeneration and asynchronous processing.
- :doc:`integrations` covers Forms, Upload, Messenger and extension points.
- :doc:`testing` shows how to test processing, storage and rendering.
- :doc:`debugging` diagnoses common configuration and runtime failures.
- :doc:`security` covers trust boundaries and production budgets.
- :doc:`configuration` is the complete configuration reference.

:doc:`overview` explains the recommended reading path and the boundaries owned
by the bundle. :doc:`architecture` documents the internal structure.
