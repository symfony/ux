Configuration
=============

.. code-block:: yaml

    # config/packages/ux_upload.yaml
    ux_upload:
        storage: local
        deployment: single_node
        shared_lock: false
        flysystem_service: null

        temp_dir: '%kernel.project_dir%/var/uploads/tmp'
        completed_prefix: '.tmp/completed'
        completed_ttl: 86400

        chunk_size: 5M
        parallel_chunks: 3
        signature_expiry: 3600
        form_token_ttl: 86400
        max_pending_per_owner: 1000
        compression: false
        integrity_algorithm: sha256
        max_size: 100M
        allowed_types: []
        allow_anonymous: false
        rate_limiter: null

        local_storage:
            directory: '%kernel.project_dir%/var/uploads'

        uploaders:
            documents:
                max_size: 50M
                allowed_types: [application/pdf]
                chunk_size: 5M
                parallel_chunks: 3
                compression: false
                integrity_algorithm: sha256
                completed_ttl: 86400
                max_pending_per_owner: 100

Global Options
--------------

=========================  ========================================  =============================================================
Option                     Default                                   Description
=========================  ========================================  =============================================================
``storage``                ``local``                                 ``local`` or ``flysystem``
``deployment``             ``single_node``                           ``single_node`` or ``distributed``
``shared_lock``            ``false``                                 Assert that all nodes use a shared Symfony Lock store
``flysystem_service``      ``null``                                  Required service ID for Flysystem
``temp_dir``               ``%kernel.project_dir%/var/uploads/tmp``  Local assembly and pending directory
``completed_prefix``       ``.tmp/completed``                        Prefix for assembled temporary files
``completed_ttl``          ``86400``                                 Lifetime after successful assembly, minimum 60 seconds
``chunk_size``             ``5M``                                    Direct-upload threshold and chunk size, from 64 KiB to 64 MiB
``parallel_chunks``        ``3``                                     Concurrent chunk requests sent by the browser, from 1 to 10
``signature_expiry``       ``3600``                                  Signed upload URL lifetime
``form_token_ttl``         ``86400``                                 Form token lifetime, capped by completed expiration
``max_pending_per_owner``  ``1000``                                  Pending-session quota per upload context
``compression``            ``false``                                 Allow gzip compression for direct bodies and chunks
``integrity_algorithm``    ``sha256``                                Algorithm for the optional browser whole-file checksum
``max_size``               ``100M``                                  Server limit; ``0`` disables it
``allowed_types``          ``[]``                                    Server MIME allow-list; empty accepts all
``allow_anonymous``        ``false``                                 Accept uploads Security cannot attribute to anyone
``rate_limiter``           ``null``                                  Named Framework rate-limiter service ID for initialization
=========================  ========================================  =============================================================

``local_storage.directory`` is the root containing completed temporary objects.

Named Uploaders
---------------

Each named uploader inherits global values. It may override ``max_size``, ``allowed_types``, ``chunk_size``, ``parallel_chunks``, ``compression``, ``integrity_algorithm``, ``completed_ttl`` and ``max_pending_per_owner``.

Form options may narrow a named uploader's ``max_size`` or ``allowed_types``, never weaken them.

The effective named-uploader ``chunk_size`` is sent to the Stimulus controller.
Files no larger than that value use ``POST <upload-prefix>``; larger files use
``POST <upload-prefix>/init`` and the signed chunk routes. There is no separate
direct-upload switch. Configure the upload prefix in
``config/routes/ux_upload.yaml``; the recipe uses ``/_ux/upload``.

Distributed Deployment
----------------------

A distributed deployment requires:

.. code-block:: yaml

    # config/packages/ux_upload.yaml
    ux_upload:
        deployment: distributed
        storage: flysystem
        flysystem_service: uploads.storage
        shared_lock: true

Configure ``framework.lock`` with a store shared by every node. ``shared_lock: true`` is an assertion; UX Upload cannot verify the store topology. Local storage is rejected in distributed mode.

Optional Dependencies
---------------------

Console, CSRF, Validator, RateLimiter, SecurityBundle, Flysystem and LiveComponent integrations are optional. Configure a feature only after installing its package. The bundle registers the cleanup command only when Console is available and requires Flysystem only for ``storage: flysystem``.

Symfony Lock and MIME are core dependencies. Lock serializes upload lifecycle
operations against cleanup, while MIME provides the extension database used to
resolve ambiguous content types such as CSV and Markdown.
