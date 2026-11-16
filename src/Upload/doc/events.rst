Server Events
=============

UX Upload dispatches PHP events during a transfer. Use listeners to inspect a
completed file or record progress and failures. For events in the browser, see
:doc:`JavaScript API <javascript>`.

Event Order
-----------

.. code-block:: text

    UploadStartedEvent
        UploadProgressEvent (for each persisted chunk)
    UploadAssembledEvent

    UploadFailedEvent may occur during an unrecoverable uploader operation.

``UploadStartedEvent``
----------------------

Dispatched after session initialization. It exposes:

- ``getUploadId()``, ``getFilename()``, ``getFileSize()``, ``getMimeType()``;
- ``getTotalChunks()``, ``getUploadUrl()``, ``getChunkSize()``;
- ``isCompressionEnabled()``, ``getParallelChunks()``.

``UploadProgressEvent``
-----------------------

Dispatched after a chunk is persisted. It exposes ``getUploadId()``,
``getChunkIndex()``, ``getTotalChunks()``, ``getStoredChunks()``,
``getChunkIndices()`` and ``getPercentComplete()``.

Do not perform slow synchronous work in this high-frequency event.

.. _ux-upload-upload-assembled-event:

``UploadAssembledEvent``
------------------------

Dispatched after assembly and integrity verification, before the completed token
is returned. ``getUpload()`` returns ``UploadedFile``; ``getMetadata()`` returns
pending-session transport metadata.

A listener may call ``setUpload()`` with another ``UploadedFile``, for example
to replace the declared MIME type after inspection. It must preserve the
temporary storage contract.

This is also the extension point for synchronous malware scanning or media
inspection that must reject an upload before a token is issued.

::

    // src/EventListener/InspectUpload.php
    namespace App\EventListener;

    use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
    use Symfony\UX\Upload\Event\UploadAssembledEvent;
    use Symfony\UX\Upload\Exception\ValidationException;

    #[AsEventListener(event: UploadAssembledEvent::class, priority: 200)]
    final class InspectUpload
    {
        public function __invoke(UploadAssembledEvent $event): void
        {
            $upload = $event->getUpload();
            $stream = $upload->openStream();
            try {
                // Throw ValidationException when these bytes must be rejected.
            } finally {
                fclose($stream);
            }
        }
    }

The built-in validation listener runs at priority ``255``. Use a lower priority
when your listener should receive its detected MIME type.

Throw ``ValidationException`` only for a permanent content rejection. UX Upload
then removes the assembled object and aborts the session. Other exceptions are
treated as transient: the session and chunks remain available for a completion
retry.

Do not create domain records from this event. Assembly is not proof that a
Symfony Form or business transaction will succeed.

See :ref:`ux-upload-custom-content-validation` for checks performed during the
upload and checks that belong in your application.

``UploadFailedEvent``
---------------------

``getUploadId()`` identifies the session and ``getError()`` exposes the original
exception. Use it for structured logs and metrics. Never log signed tokens.

UX Upload does not dispatch an event when your application saves a file or
when a temporary file is removed. Handle those operations in your own services.
