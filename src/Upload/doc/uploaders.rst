Named Uploaders
===============

A named uploader is a reusable set of server-side upload settings. For example,
avatars can accept small images while documents accept larger PDFs. Select the
uploader by name on each field.

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

Select a Named Uploader
-----------------------

::

    // src/Form/ProfileType.php
    use Symfony\UX\Upload\Form\UploadType;

    $builder->add('avatar', UploadType::class, [
        'uploader' => 'avatars',
    ]);

The name is included in the signed policy and completed-upload token.
``UploadType`` rejects a token created by another uploader.

Restrict an Individual Field
----------------------------

Set ``max_size`` and ``allowed_types`` on a field to restrict its uploader's
limits further::

    $builder->add('thumbnail', UploadType::class, [
        'uploader' => 'avatars',
        'max_size' => '1M',
        'allowed_types' => ['image/webp'],
    ]);

A field cannot allow a larger file or a type rejected by its uploader. The
server enforces these restrictions when accepting the upload and the submitted
form value. Browser checks provide earlier feedback.

Expiration
----------

Per-uploader ``completed_ttl`` controls the expiration encoded in completed
temporary filenames. Choose it for that workflow's expected form duration.
