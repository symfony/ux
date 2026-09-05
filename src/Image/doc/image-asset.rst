ImageAsset persistence contract
===============================

``ImageAsset`` is the immutable, serializable result of image processing. It contains the original storage coordinates, trusted dimensions and generated variants.

Versioned shape
---------------

Every serialized asset contains ``schemaVersion``. Unknown or missing versions, invalid scalar metadata and malformed variants fail explicitly.

Variants use one shape:

.. code-block:: php

    [
        'webp' => [
            [
                'name' => 'mobile',
                'path' => '/products/photo_mobile.webp',
                'format' => 'webp',
                'mimeType' => 'image/webp',
                'width' => 640,
                'height' => 640,
                'mode' => 'crop',
                'quality' => 82,
                'position' => 'center',
                'media' => '(max-width: 40rem)',
                'density' => null,
            ],
        ],
    ]

The top-level key is always the output format and its value is always a list. The embedded ``format``, when present, must match its top-level key.

Generated variants always contain the complete fields shown above. URL-only assets constructed manually may use only ``path``, dimensions or density, and optional ``name``/``media``; any provided field is still type-checked.

Both ``ImageAsset`` and ``ImageSource`` expose their immutable public readonly properties and matching ``get…()`` methods. Use ``ImageAsset::getFilePaths()`` for the deduplicated original-plus-variants path list and ``ImageAsset::getImageSourceSet()`` for typed source traversal.

Art direction
-------------

Art direction does not change the persistence shape. Each variant carries its own nullable ``media`` query. ``ImageSourceSet`` derives media groups for ``<picture>`` rendering.

Doctrine
--------

Enable the DBAL type only when Doctrine persistence is wanted:

.. code-block:: yaml

    ux_image:
        doctrine_type: true

Corrupt JSON or a value that does not match the versioned contract raises a Doctrine conversion exception; it is never converted silently to ``null``.
