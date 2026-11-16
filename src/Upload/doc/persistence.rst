Persisting Uploaded Files
=========================

An ``UploadedFile`` remains temporary until your application saves it. Copy the
file into permanent storage, then record its location and any document metadata
in your database.

To keep the file on the local disk, ``$upload->move($directory, $name)`` works
as with ``FileType``. The examples below use ``openStream()``, which suits any
application storage.

Application Service
-------------------

The following example separates the service that saves an upload from its
storage implementation. Define a result object and storage interface in your
application::

    // src/Model/Attachment.php
    namespace App\Model;

    final class Attachment
    {
        public function __construct(public readonly string $key)
        {
        }
    }

Define an atomic storage operation for your application::

    // src/Storage/AttachmentStorage.php
    namespace App\Storage;

    use App\Model\Attachment;

    interface AttachmentStorage
    {
        /**
         * @param resource $stream
         */
        public function storeOnce(
            string $uploadId,
            mixed $stream,
            string $originalName,
            string $mimeType,
            int $size,
        ): Attachment;
    }

Implement ``AttachmentStorage`` using your final storage and uniqueness rules.
Register that implementation for the interface in your application's container.
The service that consumes an upload can then remain independent of the storage
backend::

    // src/Application/StoreAttachment.php
    namespace App\Application;

    use App\Model\Attachment;
    use App\Storage\AttachmentStorage;
    use Symfony\UX\Upload\Upload\UploadedFile;

    final class StoreAttachment
    {
        public function __construct(private readonly AttachmentStorage $storage)
        {
        }

        public function __invoke(UploadedFile $upload): Attachment
        {
            $stream = $upload->openStream();
            try {
                return $this->storage->storeOnce(
                    uploadId: $upload->getId(),
                    stream: $stream,
                    originalName: $upload->getClientOriginalName(),
                    mimeType: $upload->getMimeType(),
                    size: $upload->getSize(),
                );
            } finally {
                fclose($stream);
            }
        }
    }

Idempotent Persistence
----------------------

A double click, proxy retry or application retry can submit the same upload
twice. Make saving *idempotent*: repeating the operation returns the file
already saved instead of creating a duplicate.

Use ``UploadedFile::getId()`` to identify repeated saves, derive a stable
storage path or enforce a database uniqueness rule. A separate lookup before
the write is not sufficient because two concurrent requests can both observe a
missing record. Expose one atomic application operation, such as
``storeOnce()``, that creates the file and record once or returns the result
already associated with the upload ID.

.. caution::

    Do not copy the file and call ``delete()`` in the same form request. The
    first request may delete the temporary object while a second valid request
    is about to call ``openStream()``, causing that second request to fail.
    Keep persistence idempotent and let scheduled cleanup remove the temporary
    copy after expiry.

Doctrine Metadata
-----------------

Adapt the entity names to your application and store the permanent file
location rather than the temporary upload path::

    use App\Entity\DocumentAttachment;

    $attachment = $storeAttachment($upload);

    $document->addAttachment(new DocumentAttachment(
        storageKey: $attachment->key,
        originalName: $upload->getClientOriginalName(),
        mimeType: $upload->getMimeType(),
        size: $upload->getSize(),
    ));

    $entityManager->flush();

Flysystem
---------

You can use Flysystem for temporary uploads and permanent files:

- UX Upload can use a configured Flysystem service for temporary transfer;
- your application can use the same or another service for final files.

Even when both use the same provider, copy into a path outside
``completed_prefix``. UX Upload cleanup owns that prefix.

Handling Storage and Database Failures
--------------------------------------

Storage and database writes are not one atomic transaction. Define compensation
for each failure direction:

- delete the final object when the database transaction fails;
- do not persist a final key when the storage write fails;
- use an application idempotency key whenever a request may be repeated;
- keep temporary cleanup outside the form request.

For expensive asynchronous processing, first copy the file, persist an
application record, then dispatch your own message after the database
transaction.
