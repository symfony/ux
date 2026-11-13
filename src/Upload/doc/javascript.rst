JavaScript API
==============

The form theme wires the ``symfony--ux-upload--upload`` Stimulus controller. No custom JavaScript is required for normal Symfony Form usage.

Twig remains the source of presentation markup. Upload items, errors, previews,
icons and aggregate progress are rendered by the form theme. JavaScript updates
text, values, ``hidden``, ``disabled``, ARIA and state attributes; it does not
inject presentation HTML.

DOM Events
----------

.. code-block:: javascript

    // assets/controllers/upload_observer_controller.js
    document.addEventListener('symfony--ux-upload--upload:progress', (event) => {
        const { fileId, percent, speed } = event.detail;
    });

    document.addEventListener('symfony--ux-upload--upload:complete', (event) => {
        const { fileId, result } = event.detail;
        // result.token is the signed completed-upload token.
    });

    document.addEventListener('symfony--ux-upload--upload:remove', (event) => {
        const { fileId } = event.detail;
    });

The controller also emits ``add``, ``start``, ``init``, ``chunk``, ``error``, ``cancel``, ``pause``, ``resume``, ``retry`` and ``validation-error`` with the same prefix. The ``init`` detail contains ``uploadId``, ``fileId`` and a ``resumable`` boolean.

Remove
------

Removing a completed item calls the ``ux_upload_remove`` route, which the
recipe exposes as ``DELETE /_ux/upload/remove``. The request sends its signed
token and, when available, CSRF and field-policy credentials. The backend
verifies signature, expiration and upload context before deleting the temporary
object.

Removing an in-progress item cancels its pending session instead.

Standalone ``Uploader``
-----------------------

Custom frontends may import the transport:

.. code-block:: javascript

    import { Uploader } from '@symfony/ux-upload';

    const uploader = new Uploader({
        directUrl: bootstrap.directUrl,
        directUploadThreshold: bootstrap.chunkSize,
        initUrl: bootstrap.initUrl,
        removeUrl: bootstrap.removeUrl,
        uploader: bootstrap.uploader,
        csrfToken: bootstrap.csrfToken,
        policyToken: bootstrap.policyToken,
        integrityAlgorithm: bootstrap.integrityAlgorithm,
        compression: bootstrap.compression,
        credentials: 'same-origin',
        events: {
            onInit(uploadId, file, resumable) {
                console.log(uploadId, file.name, resumable);
            },
            onProgress(uploadId, percent) {
                console.log(uploadId, percent);
            },
            onComplete(uploadId, result) {
                document.querySelector('#document_token').value = JSON.stringify(result);
            },
        },
    });

    await uploader.upload(file);

=========================  ========================  ============================================
Option                     Default                   Purpose
=========================  ========================  ============================================
``initUrl``                required                  Initialization endpoint
``directUrl``              disabled                  One-request multipart endpoint
``directUploadThreshold``  ``0``                     Maximum original file size for direct upload
``removeUrl``              derived from ``initUrl``  Completed temporary removal endpoint
``events``                 ``{}``                    Transport callbacks
``uploader``               ``default``               Named uploader
``csrfToken``              ``null``                  Optional CSRF token
``policyToken``            ``null``                  Signed field policy
``integrityAlgorithm``     ``sha256``                ``sha256``, ``sha384`` or ``sha512``
``compression``            ``false``                 Permit gzip for direct bodies and chunks
``credentials``            browser default           Fetch credential mode
``headers``                ``{}``                    Additional request headers
``fetch``                  global ``fetch``          Injectable chunk and request transport
``xhr``                    ``XMLHttpRequest``        Injectable direct-upload XHR factory
=========================  ========================  ============================================

The default one-request transport uses ``XMLHttpRequest`` so the browser can
report upload progress. ``onDirectProgress(file, percent, speed)`` receives
that progress before an upload ID exists; the form controller maps it to the
same DOM ``progress`` event used by chunked transfers.

Supplying a custom ``fetch`` without ``xhr`` keeps the custom direct transport,
but browsers do not expose upload progress for ``fetch``. Setting
``credentials: 'omit'`` also selects ``fetch`` so same-origin cookies are
actually omitted. Supply both ``fetch`` and ``xhr`` when a custom frontend
needs custom chunk requests and direct-upload progress.

Use ``cancel(uploadId)`` to abort and delete a pending session, ``suspend(uploadId)`` to preserve it for resume, ``pause(uploadId)`` and ``resume(uploadId)`` for local scheduling, ``cancelFile(file)`` to abort an in-flight direct request, and ``remove(token)`` for a completed temporary object.

The third ``onInit`` argument is ``true`` only when the selected transport is
resumable. It is therefore also ``true`` when a direct request receives ``413``
and falls back to the chunk protocol.

The form controller supplies ``directUrl`` and the effective named-uploader ``chunkSize`` automatically. Custom JavaScript must supply both options to enable the direct path.

.. note::

    The ``chunkSize`` Stimulus value only selects the direct-versus-chunked
    threshold on the client. The actual chunk size used for slicing always
    comes from the server response at initialization; changing the
    data attribute does not change how files are chunked.

Integrity and Compression
-------------------------

For files up to 64 MiB, the browser calculates the configured Web Crypto digest
and the backend accepts only the same configured algorithm. Supported values are
SHA-256, SHA-384 and SHA-512. This whole-file checksum is optional: the upload
continues without it when Web Crypto is unavailable or calculation fails, and
completed metadata then contains no checksum.

Transfer bodies are uncompressed by default. When compression is enabled by both the field and uploader and ``CompressionStream`` is available, direct bodies and chunks are gzip-compressed. Compressed chunks declare it with a ``Content-Encoding: gzip`` request header and compressed direct bodies with a ``contentEncoding`` form field; the backend only decompresses declared bodies and never inspects the bytes to guess. The per-part SHA-256 digest always describes the original uncompressed bytes and is independent from the optional whole-file checksum.

The direct request is never retried automatically after a network error because the browser cannot know whether the server completed it. Only an explicit ``413 Payload Too Large`` response switches that file to the chunk protocol.

The configured CSRF token is added automatically to every mutation request:
initialization, chunk transfer, completion, cancellation, resume exchange and
completed-file removal. The direct multipart request receives it as well.
Read-only requests do not receive the header.

Resume
------

IndexedDB stores a signed resume token, expiration and file fingerprint, never
file bytes. Selecting the same file can request a fresh upload URL and transfer
only missing chunks.

Clipboard
---------

The upload controller accepts clipboard files when the dropzone has focus.
Copied files retain their names; anonymous screenshots receive a timestamped
name before normal validation and transfer.

The same ``max_size``, ``max_files``, ``allowed_types``, named uploader, integrity and security rules apply. Paste does not bypass the Symfony Form or server policy.

For custom frontends:

.. code-block:: javascript

    import { extractFilesFromClipboard } from '@symfony/ux-upload';

    dropzone.addEventListener('paste', (event) => {
        const files = extractFilesFromClipboard(event);
        for (const file of files) {
            uploader.upload(file);
        }
    });

``extractFilesFromClipboard()`` already applies ``renameAnonymousFile()``. The second export is available when a custom source needs the same filename normalization. After completion, use the returned signed token in the same form payload as a selected or dropped file.
