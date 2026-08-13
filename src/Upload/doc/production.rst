Production
==========

Checklist
---------

- keep temporary directories and prefixes outside public delivery;
- accept at least one chunk plus protocol overhead in proxy and PHP limits;
- set request timeouts for the slowest expected chunk;
- configure a stable shared ``APP_SECRET``;
- schedule ``ux:upload:cleanup``;
- monitor initialization, chunk, completion and cleanup failures;
- test uploads through the real proxy and storage backend;
- use shared Flysystem storage and a shared lock store on multiple nodes.

Cleanup
-------

.. code-block:: terminal

    $ php bin/console ux:upload:cleanup --age=24h

``--age`` controls interrupted pending sessions. Completed temporary objects use the expiration timestamp in their generated key. Their ``completed_ttl`` starts when assembly succeeds, not when the pending upload was initialized.

Choose ``completed_ttl`` long enough for legitimate form correction and resubmission, but short enough to bound unused storage. A successfully consumed temporary copy should normally remain until that expiration. Keep cleanup outside the form request so retries and concurrent submissions can safely resolve the same idempotent application operation.

Use explicit ``delete()`` only when the application can prove that no retry can still use the completed token.

The command has no batch-size or cursor option. Local projects can normally run
it as shown above. For a large remote object inventory, use provider lifecycle
rules or an application-owned worker with provider-specific pagination and
observability. Keep any provider lifecycle rule longer than the Bundle TTL so
it cannot delete a still-valid form value.

Multiple Nodes
--------------

.. code-block:: yaml

    # config/packages/ux_upload.yaml
    ux_upload:
        deployment: distributed
        storage: flysystem
        flysystem_service: uploads.storage
        shared_lock: true

All nodes must share temporary storage, `Symfony Lock`_ state and ``APP_SECRET``. Local storage is rejected in distributed mode.

Cleanup uses a non-blocking per-upload lifecycle lock and skips active
transfers. In distributed deployments, configure that required Lock component
with a store shared by every node.

Object Storage
--------------

The built-in Flysystem adapter accepts any configured
``FilesystemOperator``, including one backed by S3, Azure or GCS. UX Upload does
not provide provider-specific adapters or browser-to-cloud protocols.

Generic Flysystem assembly uses a local disk-backed stream before writing the
completed object. It does not create presigned browser-to-cloud uploads.

UX Upload targets normal application uploads within a deliberate ``max_size``.
It is not intended for multi-gigabyte ingestion. Use the provider's native
browser multipart flow when that is the product requirement.

Capacity
--------

The defaults limit one field to one file, one uploader to 100 MiB, chunks to 5
MiB and parallel chunk requests to three. Increasing ``max_files``,
``chunk_size`` or ``parallel_chunks`` is an application and infrastructure
decision. A useful upper-bound estimate is active files multiplied by parallel
chunks multiplied by chunk size, plus protocol and assembly overhead.

``openStream()`` uses the backend read stream. Form submission itself performs no remote object request.

Observability
-------------

Record:

- initialization rejection reasons and pending quota usage;
- chunk retries and completion latency;
- ``UploadFailedEvent`` exception classes;
- completed-prefix size and cleanup duration;
- failures in application-owned storage separately from Bundle transfer errors.

Never log signed URLs or tokens.

.. _`Symfony Lock`: https://symfony.com/doc/current/lock.html
