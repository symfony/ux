Security
========

Identify who may upload files and keep that identity stable while the user
completes the form. UX Upload checks this *upload context* on requests and
submitted file references. Your application controls who may access the files
it saves permanently.

For size, file-type and content checks, see :doc:`File Validation <validation>`.

Who May Upload
--------------

By default, the endpoints require an owner or tenant in the resolved upload
context. With SecurityBundle, the default resolver reads the authenticated user.
A firewall alone does not provide an identity for an unauthenticated visitor.

For public forms, an application resolver can assign a server-generated owner ID
to each session. Such a context keeps ``allow_anonymous: false`` and isolates
visitors' tokens and quotas. Use a stable identity for the whole form workflow;
never trust an owner ID supplied by the browser.

Without an owner or a tenant, every visitor would share one ``UploadContext``.
The quota would then be global rather than per user, and one actor filling it
would lock out everyone else. Accepting that shared context is an explicit
opt-in:

.. code-block:: yaml

    # config/packages/ux_upload.yaml
    ux_upload:
        allow_anonymous: true

Enable it only for endpoints intended to accept an unidentified context, and
configure a ``rate_limiter``. If a resolver supplies a session owner or tenant,
the anonymous opt-in is unnecessary.

.. note::

    CSRF protection follows the same shape. ``symfony/security-bundle`` requires
    ``symfony/security-csrf``, so an application with a firewall validates the
    ``X-CSRF-Token`` header on every mutation without any extra step.

Session-bound Public Forms
--------------------------

Enable Symfony sessions for a public form whose visitors are not logged in:

.. code-block:: yaml

    # config/packages/framework.yaml
    framework:
        session: true

Use a server-generated session identity instead of a shared anonymous context::

    // src/Upload/SessionUploadContextResolver.php
    namespace App\Upload;

    use Symfony\Component\HttpFoundation\RequestStack;
    use Symfony\UX\Upload\Security\UploadContext;
    use Symfony\UX\Upload\Security\UploadContextResolverInterface;

    final class SessionUploadContextResolver implements UploadContextResolverInterface
    {
        public function __construct(private readonly RequestStack $requestStack)
        {
        }

        public function resolve(): UploadContext
        {
            $request = $this->requestStack->getMainRequest();
            if (null === $request || !$request->hasSession()) {
                return new UploadContext();
            }

            $session = $request->getSession();
            $owner = $session->get('upload_owner');
            if (!\is_string($owner) || '' === $owner) {
                $owner = bin2hex(random_bytes(16));
                $session->set('upload_owner', $owner);
            }

            return new UploadContext(ownerId: $owner);
        }
    }

Register this resolver for the application. Keep ``allow_anonymous`` disabled:

.. code-block:: yaml

    # config/services.yaml
    services:
        Symfony\UX\Upload\Security\UploadContextResolverInterface:
            alias: App\Upload\SessionUploadContextResolver

This example assumes the default ``App\`` service discovery covers ``src/``.
The resolver returns an empty context when no session is available, so the
endpoints refuse that upload with the default policy. The session identity
isolates temporary uploads; it does not replace application authorization.

Signed URLs and Tokens
----------------------

All signed values use ``kernel.secret``:

======================  =========================================================
Value                   Purpose
======================  =========================================================
Upload URL              Access one pending chunk session
Resume token            Exchange an owner-bound session for a fresh upload URL
Form policy             Restrict uploader, size, MIME types, count and field
Completed-upload token  Reconstruct signed metadata for Forms and Live Components
======================  =========================================================

Each of these payloads carries its own kind, so a value signed for one purpose
is rejected when presented as another, even though all four share the same
signing key.

Treat these values as bearer credentials. Do not log complete tokens or signed
URLs. Keep ``APP_SECRET`` stable across nodes and deployments.

Token Resolution
----------------

The completed-upload token signs the ID, uploader, temporary path, original
name, MIME type, size, creation and expiration timestamps, optional checksum,
and owner/tenant/field context.

Resolving it verifies:

- URI signature and token length;
- token and completed-file expiration;
- that the path remains below ``completed_prefix``;
- owner, tenant and form-field context;
- checksum/algorithm consistency.

Resolution then creates a lazy ``UploadedFile``. It intentionally performs no
``exists()``, metadata lookup or object read. Resolving a token and reading its
metadata do not fetch the file. Constraints such as ``File`` or ``Image``, path
access, the profiler and application code can still read storage during
submission. See :ref:`ux-upload-storage-reads`.

Owner, Tenant and Field Binding
-------------------------------

With SecurityBundle, the default resolver binds uploads to
``UserInterface::getUserIdentifier()``. Anonymous mode uses bearer credentials.

For applications with tenants or API authentication, resolve the identity from
your trusted application services instead of the session::

    // src/Upload/TenantUploadContextResolver.php
    namespace App\Upload;

    use App\Tenant\CurrentTenant;
    use Symfony\UX\Upload\Security\UploadContext;
    use Symfony\UX\Upload\Security\UploadContextResolverInterface;

    final class TenantUploadContextResolver implements UploadContextResolverInterface
    {
        public function __construct(private readonly CurrentTenant $tenant)
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

``CurrentTenant`` is an application service exposing the current user and tenant
IDs. Register the resolver as the upload context service:

.. code-block:: yaml

    # config/services.yaml
    services:
        Symfony\UX\Upload\Security\UploadContextResolverInterface:
            alias: App\Upload\TenantUploadContextResolver

Never construct the context from untrusted request values. ``UploadType`` adds
its canonical field path to the signed policy and validates the same path on
submission. Dynamic ``CollectionType`` indices are normalized to ``*`` so a
valid upload survives collection reindexing without becoming valid for another
field or collection.

CSRF
----

When ``symfony/security-csrf`` is installed, the form controller sends the
``ux_upload`` token on direct upload, initialization, chunk transfer,
completion, cancellation, resume exchange and completed-file removal. Every
internal mutation endpoint validates it. With the prefix shown in
:doc:`Installation <installation>`, the direct endpoint is ``POST /_ux/upload``
and removal uses ``DELETE /_ux/upload/remove``. The removal endpoint also
verifies the signed completed-upload token and its context before deleting.

CSRF is an optional dependency. Without it, signed policies, URLs and upload
context remain the authorization boundary.
