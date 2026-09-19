Symfony UX Upload
=================

**EXPERIMENTAL** This bundle is currently experimental and is likely to change, possibly significantly, before its first stable release.

UX Upload turns a Symfony file field into a complete upload field whose value
survives form validation and re-rendering. It provides an accessible
drag-and-drop interface with progress, multiple files, paste, retry and removal,
plus an optional preview gallery. Uploaded files survive validation errors and
form re-renders without being selected or transferred again. It is part of
`the Symfony UX initiative`_.

By default, files are transferred as soon as they are selected. Small files use one request; larger files use a resumable chunk protocol with pause and resume. UX Upload keeps the result in temporary storage and returns a lazy ``CompletedUpload``; the application decides if and where to persist it permanently.

Here, a larger file is one above ``chunk_size`` but still within the configured
application limit, which defaults to 100 MiB. UX Upload is not designed for
multi-gigabyte browser-to-cloud ingestion. Use a provider-native direct or
multipart upload protocol for that workload.

Key Features
------------

- ready-to-use accessible upload UI through ``FileUploadType``;
- drag-and-drop, keyboard and clipboard selection;
- compact and dropzone layouts, optional previews, multiple files and progress;
- pause and resume for chunked uploads;
- persistent uploaded values across validation errors and form re-renders;
- no repeated transfer after an unrelated form validation error;
- one-request uploads for files up to the configured chunk size;
- chunking and bounded retries for larger files;
- local and Flysystem temporary storage;
- signed upload URLs, policies, resume tokens and completed-upload metadata;
- owner, tenant and form-field context binding;
- per-part SHA-256 integrity checks and an optional whole-file checksum for
  files up to 64 MiB;
- multiple named uploaders;
- optional LiveComponent bridge;
- explicit browser removal and scheduled TTL cleanup.

Quick Start
-----------

.. code-block:: yaml

    # config/packages/ux_upload.yaml
    ux_upload:
        uploaders:
            documents:
                max_size: 50M
                allowed_types: [application/pdf]

::

    // src/Form/DocumentType.php
    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\FormBuilderInterface;
    use Symfony\UX\Upload\Form\FileUploadType;

    final class DocumentType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options): void
        {
            $builder->add('document', FileUploadType::class, [
                'uploader' => 'documents',
            ]);
        }
    }

::

    // src/Controller/DocumentController.php
    use Symfony\UX\Upload\Upload\CompletedUpload;

    if ($form->isSubmitted() && $form->isValid()) {
        /** @var CompletedUpload|null $upload */
        $upload = $form->get('document')->getData();

        if (null !== $upload) {
            $stream = $upload->openStream();
            try {
                $storedDocument = $documentStorage->storeOnce(
                    uploadId: $upload->getId(),
                    stream: $stream,
                    originalName: $upload->getOriginalName(),
                    mimeType: $upload->getMimeType(),
                );
            } finally {
                fclose($stream);
            }
        }
    }

The ``CompletedUpload`` metadata is reconstructed from a signed form token. Form submission checks the signature, expiration and upload context without calling the storage backend. Only ``openStream()`` reads bytes. ``storeOnce()`` above is an application method that must atomically enforce uniqueness on the upload ID so the same valid form request cannot create two permanent files.

Documentation
-------------

.. toctree::
    :hidden:

    installation
    form
    named-uploaders
    architecture
    upload-lifecycle
    storage
    persisting-uploaded-files
    javascript
    customizing-upload-field
    events
    validation
    security
    retry-and-resume
    live-component
    production
    testing
    debugging
    configuration

Getting Started
~~~~~~~~~~~~~~~

- :doc:`Installation <installation>`
- :doc:`Symfony Forms <form>`
- :doc:`Named Uploaders <named-uploaders>`

How It Works
~~~~~~~~~~~~

- :doc:`Architecture <architecture>`
- :doc:`Upload Lifecycle <upload-lifecycle>`
- :doc:`Temporary Storage <storage>`
- :doc:`Persisting Uploaded Files <persisting-uploaded-files>`

Customization
~~~~~~~~~~~~~

- :doc:`JavaScript API <javascript>`

  - Includes clipboard uploads and the standalone ``Uploader``.

- :doc:`Customizing the Upload Field <customizing-upload-field>`

  - Overridable form-theme blocks, layouts, previews and optional CSS.

- :doc:`Server Events <events>`

Reliability and Security
~~~~~~~~~~~~~~~~~~~~~~~~

- :doc:`File Validation <validation>`
- :doc:`Security <security>`
- :doc:`Retry and Resume <retry-and-resume>`

UX Integration
~~~~~~~~~~~~~~

- :doc:`Live Components <live-component>`

Operations
~~~~~~~~~~

- :doc:`Production <production>`
- :doc:`Testing Upload Workflows <testing>`
- :doc:`Debugging <debugging>`

Reference
~~~~~~~~~

- :doc:`Configuration <configuration>`

.. _`the Symfony UX initiative`: https://ux.symfony.com/
