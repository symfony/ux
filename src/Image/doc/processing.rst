Processing
==========

Uploaded filenames and declared MIME types are not used to choose an image format. The pipeline inspects the binary image signature, normalizes JPEG to ``jpeg``, and generates a random server-side filename. SVG is rejected by default. See :doc:`security`.

Both built-in drivers use the same geometry contract: ``fit`` preserves the whole image and never upscales, ``crop`` covers the target around a normalized focal point, and ``fill`` fits the image on a target-sized canvas. Requested codecs are validated for explicit GD profiles and encoding never falls back to another binary format.

Durable regeneration is provider-first. The application must implement both ``ImageAssetProviderInterface`` and ``ImageAssetPersisterInterface``; storage is never scanned. The command consumes bounded typed batches and persists every updated ``ImageAsset`` before advancing its opaque checkpoint. See :doc:`regeneration`.

``processing: async`` is an experimental transport hook. The application must provide ``ImageProcessingDispatcherInterface``; UX Image stores the inspected original, dispatches its asset and profile, and returns without generating variants in the request. Configuration fails at runtime with an actionable exception if an async profile is used without a dispatcher. The bundle does not provide a message, handler, owner identifier or persistence update; the application owns that complete lifecycle, including retries and idempotency.

.. code-block:: yaml

    # config/services.yaml
    services:
        App\Image\MessengerImageProcessingDispatcher: ~

        Symfony\UX\Image\Async\ImageProcessingDispatcherInterface:
            alias: App\Image\MessengerImageProcessingDispatcher

Concept
-------

Processing is the first stage of the pipeline. An ``UploadedFile`` goes in; variant files and an ``ImageAsset`` value object come out. Processing is profile-driven: the profile defines what formats to produce and what variant dimensions to create; the ``driver`` config selects the backend.

An explicitly requested profile must exist; a typo raises an exception before the original is stored. Omitting the profile uses the built-in ``responsive_default`` profile when the bundle configuration is loaded. Pass an explicit profile in application code so the processing contract is visible at the call site.

Profiles
--------

A profile is a named set of processing rules.

.. code-block:: yaml

    # config/packages/ux_image.yaml
    ux_image:
        profiles:
            product:
                formats: [avif, webp, jpeg] # formats to generate per variant
                variants:
                    thumbnail:
                        width: 300
                        height: 300
                        mode: crop # crop to the target rectangle
                    card:
                        width: 600 # height auto-calculated
                    hero:
                        width: 1200
                processing: immediate # immediate | deferred | async
                sizes: '(min-width: 768px) 50vw, 100vw' # sizes for <img> in this profile

The storage a variant is written to is not a profile key. It is chosen at processing time via the ``$storage`` argument to ``ImageProcessorInterface::process()`` (default ``default_public``).

Variant modes
~~~~~~~~~~~~~

========  ================  ===================================  ================  ==================================
Mode      Keeps all pixels  Exact canvas                         Uses focal point  Typical use
========  ================  ===================================  ================  ==================================
``fit``   yes               no                                   no                fluid content image
``crop``  no                yes when the source is large enough  yes               avatar, thumbnail, hero crop
``fill``  yes               yes                                  no                fixed card canvas without cropping
========  ================  ===================================  ================  ==================================

When only ``width`` is given (no ``height``), the image is resized proportionally. No mode upscales a smaller source.

Focal points
~~~~~~~~~~~~

``position`` controls which part of the source survives a ``crop``. Use a named edge or normalized percentages:

.. code-block:: yaml

    # config/packages/ux_image.yaml
    ux_image:
        profiles:
            editorial:
                formats: [jpeg]
                variants:
                    square:
                        width: 600
                        height: 600
                        mode: crop
                        position: '50% 30%'
                    subject_left:
                        width: 1200
                        height: 630
                        mode: crop
                        position: left

The first percentage is the horizontal coordinate and the second is vertical: ``0% 0%`` is the top-left corner, ``50% 50%`` is the center and ``100% 100%`` is the bottom-right corner. Named values are ``top``, ``bottom``, ``left``, ``right`` and ``center``.

.. note::

    ``position`` has an effect only with ``mode: crop``. ``fit`` and ``fill``
    preserve the complete source.

Formats and fallback
~~~~~~~~~~~~~~~~~~~~

Every variant is encoded once per configured format. Choose the format list with both
driver support and browser fallback in mind:

===================  ================================================================  ==================================
Format               Driver requirement                                                Rendering role
===================  ================================================================  ==================================
``jpeg`` or ``jpg``  always available with supported GD                                universal photographic fallback
``png``              always available with supported GD                                lossless/alpha fallback
``webp``             GD build with ``imagewebp()``, or compatible Intervention driver  modern ``<source>``
``avif``             GD build with ``imageavif()``, or compatible Intervention driver  most preferred modern ``<source>``
===================  ================================================================  ==================================

The configured ``preferred_formats`` controls ``<source>`` order; it does not create missing files. Keep JPEG or PNG in the profile whenever the original cannot be used as the universal fallback.

.. code-block:: terminal

    $ php bin/console ux:image:validate

Run the validation command in the same PHP image as production. A laptop and a container can expose different GD codec capabilities. For built-in processing, the command encodes a small temporary image through the selected driver for every configured profile format. A complete custom ``processor_service`` owns this capability validation. The command also writes, reads and deletes a uniquely named probe through each storage, including ``default_public``; the probe is removed even when another validation step fails.

Built-in ``responsive_default`` profile
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If no ``responsive_default`` profile is defined, the bundle injects one automatically:

.. code-block:: yaml

    # injected automatically
    profiles:
        responsive_default:
            processing: immediate
            # JPEG is portable across every supported GD installation.
            # Configure AVIF/WebP explicitly when the selected driver supports them.
            formats: [jpeg]
            variants:
                mobile: { width: 640, mode: fit, quality: 80 }
                tablet: { width: 1024, mode: fit, quality: 85 }
                desktop: { width: 1920, mode: fit, quality: 90 }

This means ``ux_picture($asset)`` works without any profile config.

Drivers
-------

The ``driver`` config key selects the image processing backend.

.. code-block:: yaml

    ux_image:
        driver: gd # gd | imagick | vips

GD
~~

The default. Uses PHP's built-in GD extension. Available in most PHP installations.

``GdImageProcessor``:

- Loads source images via ``imagecreatefromjpeg`` / ``imagecreatefrompng`` / ``imagecreatefromwebp`` etc.
- Produces AVIF via ``imageavif()`` (PHP 8.1+), WebP via ``imagewebp()``, JPEG via ``imagejpeg()``
- Rejects SVG before decoding; the built-in driver only processes raster images

.. warning::

    SVG is rejected. Rasterize trusted SVG input with an application-owned
    service before passing the resulting file to UX Image.

Imagick and vips
~~~~~~~~~~~~~~~~

``imagick`` and ``vips`` both route through ``InterventionImageProcessor``, which requires ``intervention/image``:

.. code-block:: terminal

    $ composer require intervention/image

Set ``driver: imagick`` to use the Imagick driver. Set ``driver: vips`` to use the libvips driver, which additionally requires ``intervention/image-driver-vips`` (plus the libvips system library and the ``ext-ffi`` PHP extension):

.. code-block:: terminal

    $ composer require intervention/image-driver-vips

These requirements are checked at container compile time: a missing package fails the
build with a clear message rather than at runtime.

Custom Intervention driver
~~~~~~~~~~~~~~~~~~~~~~~~~~

To supply your own pre-configured Intervention Image driver, register it as a service and point ``driver_service`` at it:

.. code-block:: yaml

    # config/packages/ux_image.yaml
    ux_image:
        driver_service: app.image.my_intervention_driver

The referenced service must implement ``Intervention\Image\Interfaces\DriverInterface``. When ``driver_service`` is set it takes precedence over ``driver``, and processing routes through the Intervention processor. This also requires ``intervention/image``.

Custom processor
~~~~~~~~~~~~~~~~

For a complete replacement, implement the two orchestration methods of ``ImageProcessorInterface`` and select the service explicitly. This wrapper delegates to the built-in processor:

.. code-block:: php

    // src/Image/MyImageProcessor.php
    namespace App\Image;

    use Symfony\Component\HttpFoundation\File\UploadedFile;
    use Symfony\UX\Image\ImageAsset;
    use Symfony\UX\Image\Processor\ImageProcessorInterface;

    final class MyImageProcessor implements ImageProcessorInterface
    {
        public function __construct(private ImageProcessorInterface $inner)
        {
        }

        public function process(
            UploadedFile $file,
            ?string $profile = null,
            string $storage = 'default_public',
        ): ImageAsset {
            return $this->inner->process($file, $profile, $storage);
        }

        public function generateVariants(ImageAsset $imageAsset, array $variantConfigs): array
        {
            return $this->inner->generateVariants($imageAsset, $variantConfigs);
        }
    }

.. code-block:: yaml

    # config/services.yaml
    services:
        App\Image\MyImageProcessor:
            arguments:
                $inner: '@ux_image.processor.gd'

.. code-block:: yaml

    # config/packages/ux_image.yaml
    ux_image:
        processor_service: App\Image\MyImageProcessor

This bypasses built-in driver selection. The application-owned processor is also
responsible for validating its runtime dependencies and codec support.

To extend the built-in chain instead, implement ``ImageDriverInterface``, which extends the orchestration contract with the driver primitives. Configure the custom driver name and tag the service. The interface is autoconfigured with ``ux_image.processor``; declare the tag explicitly when autoconfiguration is disabled or when setting priority:

.. code-block:: yaml

    # config/packages/ux_image.yaml
    ux_image:
        driver: acme

.. code-block:: yaml

    # config/services.yaml
    services:
        App\Image\MyImageBackend:
            tags:
                - { name: ux_image.processor, priority: 100 }

Here ``MyImageBackend`` implements both contracts. ``ChainImageProcessor`` delegates to the highest-priority tagged backend whose ``supports()`` returns ``true`` for the configured ``driver``.

What happens during processing
------------------------------

Given a source file and a ``product`` profile with three variants and three formats:

1. ``extractMetadata()`` reads the original width, height, and MIME type.
2. Before allocating an image buffer, the processor resolves every variant's real
   geometry (including width-only and height-only variants), deduplicates format
   aliases, counts output artifacts, and validates the cumulative pixel budget.
3. For each variant:

   a. The source is decoded and resized once, respecting the variant ``mode``.
   b. That shared resized image is encoded to each configured format.
   c. Each resulting file is stored.
4. The original file is also stored.
5. An ``ImageAsset`` is constructed from all collected sources and returned.

On synchronous uploads to stream-backed storage, processing uses the bounded local
upload snapshot. It does not upload the original and immediately download it again.
Regeneration still reads the persisted original through a bounded stream because no
upload source exists.

Nine generated variants plus one stored original are written for a three-variant x three-format profile, for a total of ten stored objects: ``thumbnail.avif``, ``thumbnail.webp``, ``thumbnail.jpeg``, ``card.avif``, ``card.webp``, ``card.jpeg``, ``hero.avif``, ``hero.webp``, ``hero.jpeg``, plus the original upload.

.. warning::

    Each variant combined with each format produces a separate generated file.
    Including the stored original, the storage cost is
    ``N variants × M formats + 1`` objects per upload.

Triggering processing
---------------------

The public entry point is the ``ImageProcessorInterface`` service. Inject it and call ``process()`` with an ``UploadedFile``:

.. code-block:: php

    // src/Controller/ProfileController.php
    use Symfony\UX\Image\Processor\ImageProcessorInterface;

    public function upload(Request $request, ImageProcessorInterface $processor): Response
    {
        $file = $request->files->get('avatar');

        $asset = $processor->process($file, profile: 'avatar', storage: 'avatars');

        $user->setAvatar($asset);
        $this->em->flush();

        return $this->redirectToRoute('profile');
    }

``process()`` accepts only an ``UploadedFile``. To process a file that is already on disk or fetched from a URL, wrap it in an ``UploadedFile`` first.

Deferred processing
-------------------

Set ``processing: deferred`` to store the original without generating variants. Variants can be generated later via the CLI command:

.. code-block:: terminal

    $ php bin/console ux:image:regenerate --image-profile=product --storage=product_images

.. code-block:: terminal

    # Dry run: show what would be processed
    $ php bin/console ux:image:regenerate --image-profile=product --storage=product_images --dry-run

.. tip::

    Defer variant generation to a background job for batch imports to avoid
    blocking the request.

Deferred and asynchronously dispatched assets keep ``profileRevision`` unset until variants are actually generated. Regeneration therefore does not mistake a stored original for an already current generated asset.

JPEG EXIF orientation is applied before geometry is calculated. Persisted dimensions and
GD-generated output describe the displayed orientation, not the raw encoded pixel order.

Reference
---------

``ImageProcessorInterface``
~~~~~~~~~~~~~~~~~~~~~~~~~~~

========================================================================================================  ========================================
Method                                                                                                    Description
========================================================================================================  ========================================
``process(UploadedFile $file, ?string $profile = null, string $storage = 'default_public'): ImageAsset``  Full upload -> variant -> store pipeline
``generateVariants(ImageAsset $imageAsset, array $variantConfigs): array``                                Generate variants for an existing asset
========================================================================================================  ========================================

``ImageDriverInterface``
~~~~~~~~~~~~~~~~~~~~~~~~

===================================================================================================================================  ==========================================================
Method                                                                                                                               Description
===================================================================================================================================  ==========================================================
``supports(string $driver): bool``                                                                                                   Whether this driver handles the configured driver name
``resize(string $inputPath, string $outputPath, int $width, int $height, string $mode = 'fit', string $position = 'center'): void``  Resize a single file; throws on failure
``convert(string $inputPath, string $outputPath, string $format, int $quality = 80): void``                                          Encode a file to a format; throws on failure
``extractMetadata(UploadedFile $file): array{width, height, mime, format}``                                                          Best-effort compatibility metadata; fields may be ``null``
===================================================================================================================================  ==========================================================

``ImageInspectorInterface::inspect()`` is the lower-level best-effort equivalent: it returns the same nullable array and does not enforce processing limits. ``inspectImage()`` is the strict processing API: it returns an ``InspectedImage`` value object and rejects missing, unsupported or over-limit input. Processor pipelines validate with ``inspectImage()`` even though ``extractMetadata()`` remains available for compatibility and UI inspection. Read its trusted values through ``getFormat()``, ``getMimeType()``, ``getWidth()``, ``getHeight()``, ``getBytes()`` and ``getPixelCount()``.

``generateVariants()`` returns the canonical ``array<string, list<ImageSource::toArray()>>`` persistence shape: the top-level key is the format, and every generated entry contains ``name``, ``path``, ``format``, ``mimeType``, ``width``, ``height``, ``mode``, ``quality``, ``position``, nullable ``media`` and nullable ``density``. ``ImageSource`` is the single persisted source model. Its ``generated()`` constructor creates the complete generated-variant shape.

Profile config keys
~~~~~~~~~~~~~~~~~~~

============================  ========================  ===========================================
Key                           Default                   Description
============================  ========================  ===========================================
``processing``                ``immediate``             ``immediate``, ``deferred`` or ``async``
``sizes``                     global ``default_sizes``  HTML ``sizes`` attribute for this profile
``preferred_formats``         *(inherits global)*       Optional per-profile format priority
``formats``                   ``[webp, jpeg]``          Formats to generate per variant (non-empty)
``variants``                  ``{}``                    Map of variant name -> settings
``variants.<name>.width``     *(required or height)*    Target width in pixels
``variants.<name>.height``    *(optional)*              Target height in pixels
``variants.<name>.mode``      ``fit``                   ``crop`` \| ``fit`` \| ``fill``
``variants.<name>.quality``   ``80``                    Encoding quality (1-100)
``variants.<name>.density``   ``null``                  Density descriptor (``1x``, ``2x``, ``3x``)
``variants.<name>.media``     ``null``                  Media query for art direction
``variants.<name>.position``  ``center``                Crop position
============================  ========================  ===========================================
