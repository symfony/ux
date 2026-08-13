Named Uploaders
===============

Named uploaders keep transport policy on the server and let different fields use
different limits.

Configure Uploaders
-------------------

.. code-block:: yaml

    # config/packages/ux_upload.yaml
    ux_upload:
        uploaders:
            avatars:
                max_size: 5M
                allowed_types: [image/jpeg, image/png, image/webp]
                chunk_size: 1M
                parallel_chunks: 2
                compression: false
                integrity_algorithm: sha256
                completed_ttl: 3600

            documents:
                max_size: 100M
                allowed_types: [application/pdf]
                chunk_size: 5M
                parallel_chunks: 3
                completed_ttl: 86400

Select An Uploader
------------------

::

    // src/Form/ProfileType.php
    $builder->add('avatar', FileUploadType::class, [
        'uploader' => 'avatars',
    ]);

The name is included in the signed policy and completed-upload token. ``FileUploadType`` rejects a token created by another uploader.

Narrow A Policy
---------------

Form-level ``max_size`` and ``allowed_types`` may narrow the named policy:

::

    $builder->add('thumbnail', FileUploadType::class, [
        'uploader' => 'avatars',
        'max_size' => '1M',
        'allowed_types' => ['image/webp'],
    ]);

They cannot widen it. Enforcement occurs during initialization, assembly and
form transformation; browser constraints are only early feedback.

Expiration
----------

Per-uploader ``completed_ttl`` controls the expiration encoded in completed temporary filenames. Choose it for that workflow's expected form duration.
