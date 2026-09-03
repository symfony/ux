Debugging
=========

Variants not generated
----------------------

Upload succeeds but no variant files appear
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Symptom**: The original image is stored correctly, but the expected variant files (thumbnails, different sizes) are missing from storage.

**Cause**: The profile is not configured, the image processor is not available, or the profile uses ``processing: deferred``.

**Fix**:

1. Verify the profile exists and has variants defined:

.. code-block:: yaml

    # config/packages/ux_image.yaml
    ux_image:
        profiles:
            product:
                formats: [webp, jpeg]
                variants:
                    thumbnail: { width: 300, height: 300, mode: crop }

2. Check that the configured driver extension is loaded:

.. code-block:: terminal

    $ php -m | grep -E 'gd|imagick'

If neither is present, install one. GD is included in most PHP distributions. Imagick requires ``pecl install imagick``.

3. With ``processing: deferred``, variants are not generated during upload. Run the regeneration command manually:

.. code-block:: terminal

    $ php bin/console ux:image:regenerate --image-profile=product --storage=product_images

4. Open the Symfony profiler's UX Image panel to inspect the resolved driver, storages,
   profiles and configuration warnings. Runtime processing failures are not collected by
   this panel: inspect the thrown exception and the application or worker logs for the
   failing upload.

Broken images in browser
------------------------

``<picture>`` renders but images return 404
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Symptom**: The HTML contains valid ``<picture>`` and ``<source>`` tags, but the browser shows broken images. The Network tab shows 404 responses for variant URLs.

**Cause**: The ``public_url_prefix`` does not match the web server's document root, or the storage directory is not readable by the web server.

**Fix**:

1. Verify ``public_url_prefix`` points to the correct path:

.. code-block:: yaml

    # config/packages/ux_image.yaml
    ux_image:
        storages:
            product_images:
                public_url_prefix: /uploads/products

The prefix is prepended to the stored path. If files are at ``/var/www/public/uploads/products/photo.webp``, the prefix must be ``/uploads/products``.

2. Check file permissions:

.. code-block:: terminal

    $ ls -la public/uploads/products/
    # Files should be readable by the web server user (www-data, nginx, etc.)

3. For Nginx, verify the location block serves static files from the correct root. For Apache, ensure ``AllowOverride`` permits access.

4. If using Flysystem with a cloud provider, verify the bucket/container is publicly
   accessible or has the correct access policy.

Wrong format served
-------------------

Browser gets JPEG when AVIF is configured
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Symptom**: The ``<source>`` tags list AVIF as the first format, but the browser downloads JPEG instead. Or the expected format file does not exist.

**Cause**: The AVIF (or WebP) extension is not enabled in GD/Imagick, or ``preferred_formats`` order is wrong.

**Fix**:

1. Check which formats GD supports:

.. code-block:: php

    var_dump(gd_info());
    // Look for 'AVIF Support' => true, 'WebP Support' => true

For Imagick:

.. code-block:: terminal

    $ php -r "echo implode(', ', \Imagick::queryFormats());"

The bundle does not silently skip a configured format. With the default GD driver,
unsupported output codecs fail while the container is compiled. Remove the format or use
a driver that can encode it.

2. Verify ``preferred_formats`` order in config:

.. code-block:: yaml

    ux_image:
        preferred_formats: [avif, webp, jpeg, png]

The browser picks the first ``<source>`` type it supports. AVIF should come first for smallest file size.

3. If the AVIF variant file exists but the browser still loads JPEG, the server may not send the correct ``Content-Type`` header. Add the MIME type to your web server config:

::

    # Nginx
    types {
        image/avif avif;
    }

    # Apache (.htaccess)
    AddType image/avif .avif

CDN URLs return 404
-------------------

Cloudinary/Imgix configured but transformations fail
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Symptom**: Generated URLs point to the CDN but return 404 or error pages. The Cloudinary/Imgix dashboard shows no transformation requests.

**Cause**: The ``base_url`` is incorrect, the origin is not configured, or the original files are not accessible to the CDN.

**Fix**:

1. Verify the CDN ``base_url``:

.. code-block:: yaml

    # config/packages/ux_image.yaml
    ux_image:
        storages:
            product_images:
                cdn:
                    provider: cloudinary
                    base_url: https://res.cloudinary.com/YOUR_CLOUD_NAME/image/upload

For Imgix, the base URL is ``https://YOUR_SOURCE.imgix.net``.

2. Confirm origin access. The CDN must be able to fetch the original file from your
   server. Check the origin/source configuration in the CDN dashboard.

3. Test the URL directly in a browser or with curl:

.. code-block:: terminal

    $ curl -I "https://res.cloudinary.com/mycloud/image/upload/w_300/uploads/photo.jpg"

A 404 means the CDN cannot reach the file at the origin. A 400 means the transformation
parameters are invalid.

4. If using signed URLs or private buckets, verify the CDN has the correct credentials
   or API key.

Doctrine column errors
----------------------

``ImageAssetType`` not recognized
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Symptom**: Doctrine throws ``Unknown column type "image_asset" requested`` when creating or updating the schema.

**Cause**: The Doctrine type is not registered.

**Fix**:

1. Opt in to the type:

.. code-block:: yaml

    ux_image:
        doctrine_type: true

2. Alternatively, register it manually in Doctrine:

.. code-block:: yaml

    # config/packages/doctrine.yaml
    doctrine:
        dbal:
            types:
                image_asset: Symfony\UX\Image\Doctrine\ImageAssetType

3. After registering, update the schema:

.. code-block:: terminal

    $ php bin/console doctrine:schema:update --dump-sql

The column should appear as a JSON (or TEXT) column in the SQL output.

Memory exhaustion during processing
-----------------------------------

Large images cause PHP to run out of memory
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Symptom**: ``Allowed memory size of X bytes exhausted`` during image upload or variant generation.

**Cause**: GD and Imagick decompress the entire image into memory. A 6000x4000 JPEG at 3 channels (RGB) requires ``6000 * 4000 * 3 = 72 MB`` of uncompressed data, regardless of the file size on disk. RGBA images (PNG with transparency) use 4 channels: ``6000 * 4000 * 4 = 96 MB``. Generating multiple variants multiplies this.

**Fix**:

1. Bound accepted work declaratively before increasing PHP memory:

.. code-block:: yaml

    # config/packages/ux_image.yaml
    ux_image:
        limits:
            max_input_bytes: 15000000
            max_width: 6000
            max_height: 6000
            max_megapixels: 24
            max_variants: 8
            max_output_megapixels: 48

Validate the effective configuration:

.. code-block:: terminal

    $ php bin/console ux:image:validate

These checks reject oversized inputs and output plans before the processor allocates the
corresponding image buffers.

2. Reduce the number or dimensions of variants. ``max_output_megapixels`` covers the cumulative encoded output, while ``max_variants`` bounds the profile fan out.

3. Measure peak memory on representative images in the same PHP SAPI and worker configuration used in production. Record ``memory_get_peak_usage(true)`` in an application metric around the processing job, and include process RSS in worker monitoring.

4. Only after those limits and measurements are in place, raise ``memory_limit`` in the ``php.ini`` or worker/container configuration dedicated to image processing. Do not change it dynamically in a controller.

5. Process variants sequentially rather than holding all in memory. The built-in
   processors already do this; custom processors should release resources between
   variants. Evaluate Imagick or Vips with production fixtures when GD's measured peak
   remains too high.

SVG rejected
------------

Upload fails with "SVG is rejected by default"
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Cause**: SVG is active content, not a trusted raster format. UX Image rejects it before storage with both built-in processors; it is never silently passed through, including when Imagick has an SVG delegate.

**Fix**: keep the default rejection for untrusted uploads. If the application
has a maintained sanitizer or rasterizer, implement ``SvgPolicyInterface`` and
alias that service in the container. The policy must return a new raster
``UploadedFile``; returning SVG is rejected. See :doc:`security` for the trust
boundary and an integration example.
