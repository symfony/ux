File Validation
===============

UX Upload validates transport metadata and assembled bytes before issuing a
completed-upload token. Browser checks provide early feedback only; server-side
validation remains authoritative.

Browser Validation
------------------

The form controller rejects files that exceed ``max_size`` or ``max_files``, or do not match ``allowed_types``, before starting a transfer. Pasted and dropped files follow the same path as files selected through the input.

These checks improve feedback but are not a security boundary. Every limit is
also represented by signed server policy and enforced by the backend.

Upload Policy
-------------

A named uploader defines the server maximums. ``FileUploadType`` may narrow its ``max_size`` and ``allowed_types``, but cannot weaken the selected uploader policy. The signed form policy binds those effective limits to the uploader, form field and upload context.

Direct and chunked uploads use the same policy and completion validation.

File Size And Count
-------------------

The declared size is checked before transfer, then the assembled byte count is
checked again at completion. A mismatch rejects the upload.

``max_files`` limits the number of completed references accepted by a form field. Treat application limits such as per-user quotas separately from this field limit.

MIME Type Detection
-------------------

``UploadAssembledEvent`` runs after all parts are assembled. The built-in validation listener inspects the completed bytes, enforces the MIME policy and may replace the event's ``CompletedUpload`` with a copy carrying the detected MIME type.

Some text formats, including CSV and Markdown, are commonly detected as ``text/plain``. In that narrow case the listener may refine the result to a ``text/*`` MIME type associated with the sanitized filename extension. It never promotes ``text/plain`` or ``application/octet-stream`` to a binary, image, archive or document MIME type from the filename.

Client filenames and MIME declarations remain untrusted. A client declaration
alone never overrides detected content.

Integrity Verification
----------------------

Every part sent by the bundled browser uploader includes a SHA-256 digest of the
original, uncompressed bytes. The backend verifies that digest before storing
the part. A direct upload contains one part, so the same check covers its
complete request body.

For files up to 64 MiB, the browser also attempts to calculate an optional whole-file checksum with the uploader's configured SHA-256, SHA-384 or SHA-512 algorithm. The backend verifies it when present. If Web Crypto is unavailable, the calculation fails or the file is larger, the upload continues without a whole-file checksum and ``CompletedUpload::getChecksum()`` returns ``null``.

Applications that require a checksum for every file, including multi-gigabyte
objects, should calculate it while copying into permanent storage or use a
checksum supplied by their storage provider.

Integrity checks detect accidental corruption and protocol mismatches. They do
not establish that a file is safe or acceptable to the application.

.. _ux-upload-custom-content-validation:

Custom Content Validation
-------------------------

Use ``UploadAssembledEvent`` for synchronous inspection that must reject the file before a token is issued, such as malware scanning or media decoding. Throw ``ValidationException`` for a permanent rejection.

See :ref:`ux-upload-upload-assembled-event` for the listener example and event-priority rules.

For expensive asynchronous inspection, first copy the upload into
application-owned storage, persist an application record, then dispatch an
application message.

Application Validation
----------------------

UX Upload does not know business rules such as account quotas, document status,
access permissions or required media dimensions. Validate those rules in the
application before making the final file available.

Do not serve a completed temporary path directly from a public directory.

Validation Errors
-----------------

A permanent completion failure removes the assembled object and aborts the
temporary session. The user must correct or select the file again.

Malformed, tampered, expired, cross-uploader, oversized, disallowed-MIME and
cross-field form values fail form transformation. An unrelated form error keeps
a valid signed upload value so the form can be corrected without transferring
the bytes again.
