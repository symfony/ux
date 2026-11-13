Architecture
============

UX Upload separates transport, temporary storage and application persistence.

.. code-block:: text

    FileUploadType/Twig
        -> Stimulus controller
        -> direct POST or chunk protocol
        -> Uploader
        -> StorageInterface
        -> signed completed token
        -> Symfony Form
        -> CompletedUpload
        -> openStream()
        -> application-owned storage

Bundle Responsibilities
-----------------------

- sign upload policies, URLs, resume records and completed metadata;
- transfer, resume, validate and assemble chunks;
- bind temporary references to owner, tenant and form field;
- retain completed objects for a configured TTL;
- delete a temporary object on explicit removal;
- prune expired completed objects and stale pending sessions.

Application Responsibilities
----------------------------

- decide whether a business operation accepts the upload;
- copy the stream to its final destination;
- define business metadata and the final storage key;
- coordinate database and storage failures;
- authorize delivery and deletion of final files.

There is deliberately no public service that decides final ownership and no
required final-storage interface.

Lazy Reference
--------------

``CompletedUpload`` contains immutable signed metadata plus a narrow internal temporary-object accessor. It does not expose or serialize the storage service or security context, and it does not serialize the temporary path. The path is available explicitly through ``getTemporaryPath()`` for diagnostics and storage adapters, but applications must not persist it. Metadata access is local. ``openStream()`` is the explicit storage read; ``delete()`` is the explicit temporary deletion.

The token handler does not query storage while reconstructing the reference. Consequently a valid token may resolve even if an external process has already deleted the object; ``openStream()`` then reports the storage failure.

Metadata Boundaries
-------------------

Bundle metadata is transport data: ID, uploader, temporary path, filename, MIME,
size, timestamps, checksum and security context.

Application metadata is domain data: document ID, title, caption, category,
permissions, final location and retention. Persist application metadata only
after your storage operation reaches the state your domain requires.

Extension Points
----------------

- named uploader configuration for policy;
- ``UploadContextResolverInterface`` for identity/tenant binding;
- ``UploadAssembledEvent`` for synchronous content checks;
- ``StorageInterface`` (extend ``AbstractStorage``) for temporary transfer backends;
- DOM events or the exported JavaScript ``Uploader`` for custom frontends.
