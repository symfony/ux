Persisting Uploaded Files
=========================

``CompletedUpload`` represents a Bundle-owned temporary file. Copy its stream into application-owned storage before persisting a domain record.

Application Service
-------------------

The primary integration point is plain application code:

::

    // src/Application/StoreAttachment.php
    use Symfony\UX\Upload\Upload\CompletedUpload;

    final readonly class StoreAttachment
    {
        public function __construct(private AttachmentStorage $storage)
        {
        }

        public function __invoke(CompletedUpload $upload): Attachment
        {
            $stream = $upload->openStream();
            try {
                return $this->storage->storeOnce(
                    uploadId: $upload->getId(),
                    stream: $stream,
                    originalName: $upload->getOriginalName(),
                    mimeType: $upload->getMimeType(),
                    size: $upload->getSize(),
                );
            } finally {
                fclose($stream);
            }
        }
    }

``AttachmentStorage`` is defined by the application. It may wrap Flysystem, an SDK, a media bundle or local filesystem code. UX Upload does not require it to implement any Bundle interface.

Idempotent Persistence
----------------------

Treat application persistence as an idempotent operation. Two requests may
submit the same signed completed-upload value: a double click, a proxy retry or
an application retry must return the same application file instead of creating a
second one.

Use ``CompletedUpload::getId()`` as an application-owned idempotency key, deterministic storage key or database uniqueness rule. A separate lookup before the write is not sufficient because two concurrent requests can both observe a missing record. Expose one atomic application operation, such as ``storeOnce()``, that creates the file and record once or returns the result already associated with the upload ID.

.. caution::

    Do not copy the file and call ``delete()`` in the same form request. The
    first request may delete the temporary object while a second valid request
    is about to call ``openStream()``, causing that second request to fail.
    Keep persistence idempotent and let scheduled cleanup remove the temporary
    copy after expiry.

Doctrine Metadata
-----------------

Persist only application-owned identifiers:

::

    $attachment = $storeAttachment($upload);

    $document->addAttachment(new DocumentAttachment(
        storageKey: $attachment->key,
        originalName: $upload->getOriginalName(),
        mimeType: $upload->getMimeType(),
        size: $upload->getSize(),
    ));

    $entityManager->flush();

.. caution::

    Do not persist ``getTemporaryPath()``: the path lives inside the Bundle's
    cleanup scope and is deleted after expiration.

Coordinate storage and database failures according to your domain. The Bundle cannot make an object-store write and a database transaction atomic.

Flysystem
---------

Flysystem may appear on both sides of the boundary:

- UX Upload can use a configured Flysystem service for temporary transfer;
- your application can use the same or another service for final files.

Even when both use the same provider, copy into a path outside ``completed_prefix``. UX Upload cleanup owns that prefix.

Failure Coordination
--------------------

Storage and database writes are not one atomic transaction. Define compensation
for each failure direction:

- delete the final object when the database transaction fails;
- do not persist a final key when the storage write fails;
- use an application idempotency key whenever a request may be repeated;
- keep temporary cleanup outside the form request.

For expensive asynchronous processing, first copy the file, persist an
application record, then dispatch your own message after the database
transaction.
