Upload Lifecycle
================

UX Upload owns a bounded temporary lifecycle, not the lifecycle of your
application documents.

.. code-block:: text

    small file -> direct POST
    large file -> init + chunks + completion
    both -> assembled temporary file
         -> CompletedUpload token
         -> TTL cleanup

Pending Session
---------------

For a direct upload, the Bundle creates a one-part session, stores part zero and
completes it within the same HTTP request. This internal session is deleted if
the request fails because it cannot be resumed.

For a chunked upload, initialization is a separate request. It creates a random
upload ID and records transport metadata. Chunks are stored under that session
and may be resumed while their signed credentials remain valid.

Cancelling an in-progress transfer aborts its pending session. A browser reload
may retain a signed resume record in IndexedDB; file bytes are never stored
there.

Completed Temporary File
------------------------

Completion verifies the chunk set and optional whole-file checksum, assembles the object, dispatches ``UploadAssembledEvent``, and stores the result below one prefix:

.. code-block:: text

    .tmp/completed/1785269400-9f1c4e2a7b8d40e3a1c2b3d4e5f60718.pdf

The basename contains the expiration timestamp and random upload ID. The extension is sanitized from the original filename. The default prefix is ``.tmp/completed``.

``completed_ttl`` starts at successful assembly. Time spent uploading, pausing or resuming does not shorten the completed temporary file's useful lifetime.

Both transports run the same completion pipeline: storage assembly, whole-file integrity checks, MIME detection, ``UploadAssembledEvent``, token generation and temporary expiration.

The browser receives a signed token. On form submission the token reconstructs a ``CompletedUpload`` from signed metadata. This resolution does not check object existence and does not read the object, which avoids a remote S3/Flysystem request on every form submission.

Application Persistence
-----------------------

``CompletedUpload`` is a temporary reference, not an ``UploadedFile`` and not an application document. The application copies its stream into final storage and persists only application-owned identifiers. UX Upload intentionally defines no interface for that storage.

See :doc:`Persisting Uploaded Files <persisting-uploaded-files>` for the complete application service, Doctrine and Flysystem examples. Do not persist ``getTemporaryPath()``; cleanup is allowed to delete that path.

Removal And Expiration
----------------------

Calling ``$upload->delete()`` removes the temporary object immediately. The widget's **Remove** action calls the authenticated internal remove endpoint and performs the same operation.

Successful application persistence does not automatically delete the temporary
copy. Keep it until the short TTL: application persistence must be idempotent,
and two successive submissions may both need to open the same temporary stream.
Deleting it in the first request can make the second request fail.

Schedule ``ux:upload:cleanup`` independently from form requests; it removes:

- completed files when the timestamp encoded in their generated key has expired;
- interrupted pending sessions older than the command's ``--age``.

Configure ``completed_ttl`` for the longest legitimate form workflow, then keep it as short as operationally practical.

Reserve explicit ``delete()`` for deliberate user removal or workflows that can prove no retry or concurrent request can still reference the token.
