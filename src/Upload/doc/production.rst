Production
==========

Before deploying, check request limits, temporary storage and cleanup through
the same proxy and storage backend your application will use in production.

Deployment Checklist
--------------------

- keep temporary directories and prefixes outside public delivery;
- accept at least one chunk plus protocol overhead in proxy and PHP limits;
- set request timeouts for the slowest expected chunk;
- configure a stable shared ``APP_SECRET``;
- identify each user or session with an upload context; enable
  ``allow_anonymous`` only when a shared unidentified context is intentional;
- configure a ``rate_limiter`` for public upload endpoints;
- schedule ``ux:upload:cleanup``;
- monitor initialization, chunk, completion and cleanup failures;
- test uploads through the real proxy and storage backend;
- use shared Flysystem storage and a shared lock store on multiple nodes.

Cleanup
-------

.. code-block:: terminal

    $ php bin/console ux:upload:cleanup --age=24h

``--age`` controls interrupted pending sessions. Completed temporary objects use
the expiration timestamp in their generated key. Their ``completed_ttl`` starts
when the completion attempt begins assembly, not when the pending upload was
initialized. Assembly and synchronous listeners consume part of that window.

Choose ``completed_ttl`` long enough for legitimate form correction and
resubmission, but short enough to bound unused storage. A successfully consumed
temporary copy should normally remain until that expiration. Keep cleanup
outside the form request so retries and concurrent submissions can safely
resolve the same idempotent application operation.

Use explicit ``delete()`` only when the application can prove that no retry can
still use the completed token.

The command has no batch-size or cursor option. Local projects can normally run
it as shown above. For a large remote object inventory, use provider lifecycle
rules or an application-owned worker with provider-specific pagination and
observability. Keep any provider lifecycle rule longer than the bundle TTL so it
cannot delete a still-valid form value.

Request Limits
--------------

The direct transport submits a multipart POST for files up to ``chunk_size``.
Set ``upload_max_filesize`` to at least that file size and ``post_max_size``
higher to leave room for multipart overhead. Alternatively, lower ``chunk_size``
so the direct request fits the configured PHP limits.

Proxy limits apply to direct requests and each chunk. The browser switches a
direct request to chunks only after HTTP 413. PHP may instead reject the
multipart file or discard the POST body; this is not a guaranteed 413 response
and does not trigger that fallback. Inspect the response and server limits
rather than retrying the same request.

Multiple Nodes
--------------

.. code-block:: yaml

    # config/packages/ux_upload.yaml
    ux_upload:
        deployment: distributed
        storage: flysystem
        flysystem_service: uploads.storage
        shared_lock: true

All nodes must share temporary storage, `Symfony Lock`_ state and
``APP_SECRET``. Local storage is rejected in distributed mode.

Cleanup uses a non-blocking per-upload lifecycle lock and skips active
transfers. In distributed deployments, configure that required Lock component
with a store shared by every node.

Object Storage and Large Files
------------------------------

The built-in Flysystem adapter accepts any configured ``FilesystemOperator``,
including one backed by S3, Azure or GCS. UX Upload does not provide
provider-specific adapters or browser-to-cloud protocols.

Generic Flysystem assembly uses a local disk-backed stream before writing the
completed object. It does not create presigned browser-to-cloud uploads.

Set ``max_size`` to match the files your application accepts.
It is not intended for multi-gigabyte ingestion. Use the provider's native
browser multipart flow when that is the product requirement.

Capacity
--------

The defaults limit one field to one file, one uploader to 100 MiB, chunks to 5
MiB and parallel chunk requests to three. Increasing ``max_files``,
``chunk_size`` or ``parallel_chunks`` is an application and infrastructure
decision. A useful upper-bound estimate is active files multiplied by parallel
chunks multiplied by chunk size, plus protocol and assembly overhead. That
estimate holds with ``compression`` enabled: decompression is fed in blocks
sized from ``chunk_size``, so a compressed chunk cannot expand beyond it.

``max_pending_per_owner`` bounds pending sessions per upload context. With an
authenticated owner that quota applies per user; under ``allow_anonymous`` every
visitor shares one context unless a resolver separates them, so the quota then
bounds the whole application at once.

``openStream()`` uses the backend read stream. Token resolution and metadata
access perform no remote object request. File constraints, path access and the
profiler may load the bytes during submission; see
:ref:`ux-upload-storage-reads`.

Observability
-------------

Record:

- initialization rejection reasons and pending quota usage;
- chunk retries and completion latency;
- ``UploadFailedEvent`` exception classes;
- completed-prefix size and cleanup duration;
- failures in application-owned storage separately from bundle transfer errors.

Never log signed URLs or tokens.

.. _`Symfony Lock`: https://symfony.com/doc/current/lock.html
