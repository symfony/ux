Testing Upload Workflows
========================

Application Service
-------------------

Use ``CompletedUploadFactory`` to test code that consumes a temporary upload:

::

    // tests/Application/StoreAttachmentTest.php
    use PHPUnit\Framework\TestCase;
    use Symfony\UX\Upload\Test\CompletedUploadFactory;

    final class StoreAttachmentTest extends TestCase
    {
        public function testItStoresTheUpload(): void
        {
            $upload = new CompletedUploadFactory(
                originalName: 'contract.txt',
                mimeType: 'text/plain',
                size: 4,
            )->create(content: 'data');

            $attachment = ($this->storeAttachment)($upload);

            self::assertSame('contract.txt', $attachment->originalName);
        }
    }

The factory writes deterministic bytes to ``InMemoryStorage`` and returns a non-expired ``CompletedUpload``.

Deterministic Upload Context
----------------------------

``TestUploadContextResolver`` resolves one fixed context so ownership and token assertions are deterministic. Inject it wherever the Bundle expects an ``UploadContextResolverInterface``:

::

    use Symfony\UX\Upload\Test\TestUploadContextResolver;
    use Symfony\UX\Upload\Token\UploadTokenHandler;

    $handler = new UploadTokenHandler(
        $signer,
        $storage,
        contextResolver: new TestUploadContextResolver(ownerId: 'user-42', tenantId: 'acme'),
    );

Without arguments the resolver returns the ``user-1`` owner and no tenant. A token issued under one context and resolved under another returns ``null``; this is how replay tests assert owner, tenant and field binding.

Important Boundaries
--------------------

==================================================  ==================================================
Question                                            Test
==================================================  ==================================================
Does domain code copy and persist the right bytes?  ``CompletedUploadFactory`` plus a unit test
Does metadata access avoid storage reads?           Mock ``StorageInterface::read()`` with ``never()``
Does form submission resolve a signed value?        Kernel/Form test with ``UploadTokenHandler``
Are owner, tenant and field replay rejected?        Token/context integration tests
Does a custom backend preserve bytes and cleanup?   Storage contract tests
Does retry/resume converge?                         JavaScript unit tests
Does the complete browser/form flow work?           A focused browser test
==================================================  ==================================================

Stream Consumption
------------------

Assert both byte correctness and resource closure in application tests. Test a
large stream when using remote storage to catch accidental buffering in your own
adapter.

Cleanup
-------

For each temporary backend, cover:

- expired completed keys are deleted;
- unexpired completed keys remain;
- files outside ``completed_prefix`` remain;
- stale pending sessions are removed according to ``--age``;
- active pending sessions remain.

LiveComponent
-------------

Inject a real ``UploadTokenHandler``, call ``applyUpload()``, assert that ``getUpload()`` returns ``CompletedUpload``, then call ``clearUpload()`` and assert that the temporary object was deleted.

The target property itself remains a signed string across component hydration.
