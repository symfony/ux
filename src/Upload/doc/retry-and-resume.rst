Retry and Resume
================

UX Upload chooses the transport from the original file size. Files at or below ``chunk_size`` use one direct request. Larger files use chunks so transient failures do not restart the complete file.

================================================  ==========================================================================  =================================
Situation                                         Bundle behavior                                                             Action
================================================  ==========================================================================  =================================
Direct request succeeds                           Complete the one-part upload                                                Usually none
Direct endpoint returns ``413``                   Retry once through the chunk protocol                                       Usually none
Direct request has a network or validation error  Do not replay an ambiguous request                                          Retry explicitly
Direct upload is cancelled                        Abort the local fetch; server cleanup or TTL handles any completed request  Upload again
One chunk fails                                   Retry up to three times after the initial request                           Usually none
Retry budget is exhausted                         Mark the file failed                                                        Use **Retry**
User pauses                                       Stop scheduling new chunks                                                  Use **Resume**
Stimulus disconnects                              Suspend local requests and preserve server session                          Select the same file
Completion storage or scanner fails transiently   Preserve chunks and session                                                 Retry completion
Completion fails validation                       Delete the assembled object and session                                     Correct the file and upload again
Another form field is invalid                     Keep the signed completed value                                             Correct and resubmit
Completed token expires                           Reject form transformation                                                  Upload again
================================================  ==========================================================================  =================================

Chunk retry delays are 1, 2 and 4 seconds. A chunk rejected with a 4xx other than
408 or 429 fails immediately, since retrying it would not change the answer.
``parallel_chunks`` caps how many chunk requests the browser sends at once; the
server serializes the writes of a given upload.

Direct uploads cannot be paused or resumed. This avoids creating an additional
session request for ordinary files while preserving resumability where it is
useful. A selection of 20 small files therefore performs 20 upload requests,
followed by the normal form submission.

Persistent Resume
-----------------

IndexedDB stores only a signed resume token, expiration and file fingerprint.
The fingerprint includes uploader, policy, endpoint, integrity algorithm,
filename, size, modification time and samples from the file.

.. code-block:: text

    select same file
        -> POST /upload/resume
        -> verify context-bound resume token
        -> GET pending chunk indexes
        -> PUT missing chunks
        -> POST completion

An invalid, expired or context-mismatched resume token is discarded.

Form Retry Without Remote Read
------------------------------

After completion, the hidden form value contains signed metadata. Symfony can
validate and re-render that value without calling temporary storage. This is
especially important with remote Flysystem backends.

The eventual ``openStream()`` may still fail if cleanup, explicit removal or an external lifecycle policy has deleted the object. Do not explicitly remove a successfully consumed temporary object in the form request: a second submission may still need it.

Idempotent Completion and Removal
---------------------------------

Committed completion metadata remains with the temporary session, so repeating a
successful completion returns the existing temporary reference. If assembly
failed before that metadata was committed, the retry reuses the uploaded chunks
but may generate a different temporary path. Removing an already absent
temporary object is treated according to the configured storage's idempotent
delete behavior.

An interrupted assembly is rebuilt unless completion metadata was committed. Unexpected storage, network or listener exceptions preserve the pending chunks so the client can retry completion without retransmitting the file. ``ValidationException`` means the bytes are definitively unacceptable: UX Upload removes the assembled object and aborts that session.

Application persistence must define its own idempotency key or uniqueness rule. Two successive calls with the same completed upload must return the same application result. The upload ID is available through ``getId()`` and is a useful input to that application-owned rule; it does not need to become a public domain identifier.

Run ``ux:upload:cleanup`` separately from form submission. This keeps retry safety independent from request timing and removes the temporary copy only after its configured expiration.
