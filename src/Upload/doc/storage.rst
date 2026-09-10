Temporary Storage
=================

Storage is an internal transport concern. It holds pending chunks, session
metadata and completed temporary objects. It is not an abstraction for your
application's final document storage.

Local Storage
-------------

The default backend writes outside ``public/``:

.. code-block:: yaml

    # config/packages/ux_upload.yaml
    ux_upload:
        storage: local
        temp_dir: '%kernel.project_dir%/var/uploads/tmp'
        completed_prefix: '.tmp/completed'
        local_storage:
            directory: '%kernel.project_dir%/var/uploads'

Typical layout:

.. code-block:: text

    var/uploads/tmp/{uploadId}/...                         pending session
    var/uploads/.tmp/completed/{expiry}-{uploadId}.pdf    completed temporary file

``CompletedUpload::openStream()`` calls the configured storage's ``read()`` method and returns a readable resource. Always close it.

Flysystem
---------

Install Flysystem and configure one explicit service:

.. code-block:: yaml

    # config/packages/ux_upload.yaml
    ux_upload:
        storage: flysystem
        flysystem_service: uploads.storage
        temp_dir: '%kernel.project_dir%/var/uploads/tmp'
        completed_prefix: '.tmp/completed'

The service ID is required because applications often configure several filesystems. UX Upload never guesses one by autowiring ``FilesystemOperator``.

The adapter is provider-neutral. Configure the ``FilesystemOperator`` with the
Flysystem adapter for S3, Azure, GCS or another backend. UX Upload does not ship
provider-specific adapters or a presigned browser-to-cloud protocol.

Generic Flysystem reads use ``readStream()``. Assembly uses a disk-backed temporary stream before writing the completed object, so it does not require holding the full file in PHP memory.

Completed Prefix and Cleanup
----------------------------

Every built-in backend writes assembled objects directly below ``completed_prefix``. Generated names encode the expiration timestamp:

.. code-block:: text

    {completed_prefix}/{expiresAt}-{uploadId}.{extension}

Cleanup lists this prefix and deletes only expired keys matching the generated shape. It does not rely on filesystem ``mtime``, object tags or provider-specific metadata. Objects outside the prefix are outside completed-upload cleanup.

The command's ``--age`` still controls stale pending sessions:

.. code-block:: terminal

    $ php bin/console ux:upload:cleanup --age=24h

Completed objects use their encoded expiration. The timestamp is calculated from ``completed_ttl`` only after assembly starts, so a slow or resumed pending transfer does not consume the completed-file retention window.

Assembly does not trust an object left behind without committed completion
metadata. Local storage rebuilds through a temporary file and atomically renames
it; Flysystem rebuilds the uncommitted object. This makes a completion retry
recover from an interrupted assembly instead of accepting partial bytes.

Cleanup takes the same per-upload Symfony Lock lifecycle lock as chunk writes,
completion and cancellation. A locked active upload is skipped and considered
again by the next cleanup run.

Application-owned files do not belong below ``completed_prefix``. See :doc:`Persisting Uploaded Files <persisting-uploaded-files>` for copying a ``CompletedUpload`` into final storage.

For object stores, configure lifecycle policies as a secondary safeguard, but keep ``ux:upload:cleanup`` as the Bundle-level cleanup mechanism.

``ux:upload:cleanup`` has no batch or cursor option. If listing the complete
temporary prefix is too expensive for a remote provider, add an
application-owned paginated cleanup worker or provider lifecycle policy. Keep
that policy longer than ``completed_ttl`` so valid form tokens remain usable.

Custom Storage Backends
-----------------------

The transport contract is two interfaces. ``StorageInterface`` combines raw
object operations (``write()``, ``read()``, ``delete()``, ``exists()``) with
the chunked session lifecycle:

- ``initiate()``, ``getMetadata()``, ``completeSession()`` and ``abort()`` manage session metadata;
- ``storeChunk()`` writes a chunk index at most once. A retry carrying the same digest is idempotent and returns ``ChunkWriteResult::AlreadyPresent``; a different digest for an already stored index must be rejected so stored bytes are never overwritten;
- ``assemble()`` concatenates the chunks and returns an ``AssembledUpload`` reporting the path, the size and, when an algorithm is given, the hash computed during the single write pass. The caller verifies integrity from this report without reading the object back;
- ``countPendingByContext()`` backs the ``max_pending_per_owner`` quota;
- ``isDistributed()`` declares whether the backend is shared between hosts. A distributed backend requires an explicitly configured shared lock, see :doc:`Production <production>`.

``PrunableStorageInterface`` adds ``prune()`` and is consumed only by the
``ux:upload:cleanup`` command.

Extend ``AbstractStorage`` rather than implementing ``StorageInterface`` from
scratch. The base class validates the completed prefix, resolves session
metadata and expiration around your ``doAssemble()`` implementation and
defaults ``isDistributed()`` to ``false``. ``InMemoryStorage`` is a complete
reference implementation and doubles as a storage for application tests.
