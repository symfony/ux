Testing Upload Workflows
========================

Use ``UploadedFileFactory`` for application unit tests and ``WebTestCase`` for
form submission. Add a browser test when you need to exercise progress, retries
or other JavaScript behavior.

Application Service
-------------------

Use ``UploadedFileFactory`` to test code that consumes a temporary upload. This
example exercises the application service and storage contract defined in
:doc:`Persisting Uploaded Files <persistence>`::

    // tests/Application/StoreAttachmentTest.php
    namespace App\Tests\Application;

    use App\Application\StoreAttachment;
    use App\Model\Attachment;
    use App\Storage\AttachmentStorage;
    use PHPUnit\Framework\TestCase;
    use Symfony\UX\Upload\Test\UploadedFileFactory;

    final class StoreAttachmentTest extends TestCase
    {
        public function testItStoresTheUpload(): void
        {
            $upload = new UploadedFileFactory(
                originalName: 'contract.txt',
                mimeType: 'text/plain',
                size: 4,
            )->create(content: 'data');
            $expected = new Attachment('documents/contract.txt');
            $consumed = null;

            $checkStream = static function (mixed $stream) use (&$consumed): bool {
                $consumed = $stream;

                return \is_resource($stream)
                    && 'data' === stream_get_contents($stream);
            };
            $storage = $this->createMock(AttachmentStorage::class);
            $storage->expects(self::once())
                ->method('storeOnce')
                ->with(
                    $upload->getId(),
                    self::callback($checkStream),
                    'contract.txt',
                    'text/plain',
                    4,
                )
                ->willReturn($expected);

            $storeAttachment = new StoreAttachment($storage);

            self::assertSame($expected, $storeAttachment($upload));
            self::assertFalse(\is_resource($consumed));
        }
    }

The test constructs its service explicitly, verifies the bytes passed to the
storage and checks that the stream was closed. A plain PHPUnit ``TestCase`` does
not inject Symfony services. Use ``KernelTestCase`` and the test container when
the purpose is to test the application's service wiring instead.

The factory writes deterministic bytes to ``InMemoryStorage`` and returns a
non-expired ``UploadedFile``.

Functional Tests
----------------

A ``WebTestCase`` has no JavaScript, so the form receives the file itself, as
with ``FileType``::

    // tests/Controller/DocumentControllerTest.php
    $crawler = $client->request('GET', '/documents/new');
    $form = $crawler->selectButton('Save document')->form();
    $form['document[document]']->upload(__DIR__.'/fixtures/contract.pdf');
    $client->submit($form);

The file goes through the uploader of the field. Use an authenticated test user
or a deterministic test context resolver. Enable ``allow_anonymous: true`` in
the ``test`` environment only when testing an unidentified public upload
context.

Deterministic Upload Context
----------------------------

``TestUploadContextResolver`` resolves one fixed context so ownership and token
assertions are deterministic. Inject it wherever the bundle expects an
``UploadContextResolverInterface``::

    use Symfony\UX\Upload\Test\TestUploadContextResolver;
    use Symfony\UX\Upload\Token\UploadTokenHandler;

    $handler = new UploadTokenHandler(
        $signer,
        $storage,
        contextResolver: new TestUploadContextResolver(
            ownerId: 'user-42',
            tenantId: 'acme',
        ),
    );

Without arguments the resolver returns the ``user-1`` owner and no tenant. A
token issued under one context and resolved under another returns ``null``; this
is how replay tests assert owner, tenant and field binding.

Choosing a Test
---------------

==================================================  ==================================================
Question                                            Test
==================================================  ==================================================
Does domain code copy and persist the right bytes?  ``UploadedFileFactory`` plus a unit test
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

Inject a real ``UploadTokenHandler``, call ``applyUpload()``, assert that a
public getter wrapping ``getUpload()`` returns ``UploadedFile``, then call
``clearUpload()`` and assert that the temporary object was deleted.

The target property itself remains a signed string across component hydration.
