Security
========

UX Upload secures transport and temporary references. Your application remains responsible for authorization and access to final files. File and business validation are documented separately in :doc:`File Validation <validation>`.

Who May Upload
--------------

The endpoints refuse an upload that Security cannot attribute to anyone. An
application with a firewall needs no configuration for this: the resolver reads
the authenticated user, so every session carries an owner and the pending quota
applies per user.

Without an owner or a tenant, every visitor would share one ``UploadContext``.
The quota would then be global rather than per user, and one actor filling it
would lock out everyone else. Accepting that is a deliberate choice::

    # config/packages/ux_upload.yaml
    ux_upload:
        allow_anonymous: true

Turn it on only for endpoints meant to be public, and pair it with a
``rate_limiter`` and an ``UploadContextResolverInterface`` that isolates
visitors from one another.

.. note::

    CSRF protection follows the same shape. ``symfony/security-bundle`` requires
    ``symfony/security-csrf``, so an application with a firewall validates the
    ``X-CSRF-Token`` header on every mutation without any extra step.

Signed Boundaries
-----------------

All signed values use ``kernel.secret``:

======================  =========================================================
Value                   Purpose
======================  =========================================================
Upload URL              Access one pending chunk session
Resume token            Exchange an owner-bound session for a fresh upload URL
Form policy             Restrict uploader, size, MIME types, count and field
Completed-upload token  Reconstruct signed metadata for Forms and Live Components
======================  =========================================================

Treat these values as bearer credentials. Do not log complete tokens or signed URLs. Keep ``APP_SECRET`` stable across nodes and deployments.

No Storage Read On Submission
-----------------------------

The completed-upload token signs the ID, uploader, temporary path, original
name, MIME type, size, creation and expiration timestamps, optional checksum,
and owner/tenant/field context.

Resolving it verifies:

- URI signature and token length;
- token and completed-file expiration;
- that the path remains below ``completed_prefix``;
- owner, tenant and form-field context;
- checksum/algorithm consistency.

Resolution then creates a lazy ``CompletedUpload``. It intentionally performs no ``exists()``, metadata lookup or object read. This prevents a remote storage request on every form submission. The actual read happens only when application code calls ``openStream()``.

Owner, Tenant and Field Binding
-------------------------------

With SecurityBundle, the default resolver binds uploads to ``UserInterface::getUserIdentifier()``. Anonymous mode uses bearer credentials.

For tenant-aware or API authentication, replace the resolver::

    // src/Upload/TenantUploadContextResolver.php
    use Symfony\UX\Upload\Security\UploadContext;
    use Symfony\UX\Upload\Security\UploadContextResolverInterface;

    final readonly class TenantUploadContextResolver implements UploadContextResolverInterface
    {
        public function __construct(private CurrentTenant $tenant)
        {
        }

        public function resolve(): UploadContext
        {
            return new UploadContext(
                ownerId: $this->tenant->userId(),
                tenantId: $this->tenant->id(),
            );
        }
    }

.. code-block:: yaml

    # config/services.yaml
    services:
        Symfony\UX\Upload\Security\UploadContextResolverInterface:
            alias: App\Upload\TenantUploadContextResolver

Never construct the context from untrusted request values. ``FileUploadType``
adds its canonical field path to the signed policy and validates the same path
on submission. Dynamic ``CollectionType`` indices are normalized to ``*`` so a
valid upload survives collection reindexing without becoming valid for another
field or collection.

CSRF
----

When ``symfony/security-csrf`` is installed, the form controller sends the
``ux_upload`` token on direct upload, initialization, chunk transfer,
completion, cancellation, resume exchange and completed-file removal. Every
internal mutation endpoint validates it. With the recipe prefix, the direct
endpoint is ``POST /_ux/upload`` and removal uses ``DELETE
/_ux/upload/remove``. The removal endpoint also verifies the signed
completed-upload token and its context before deleting.

CSRF is an optional dependency. Without it, signed policies, URLs and upload
context remain the authorization boundary.
