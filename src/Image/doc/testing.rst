Testing
========

UX Image separates pure metadata, rendering, processing, storage and persistence. Test
at the narrowest boundary that proves the application's behavior.

==============================  =========================================================
Concern                         Recommended test
==============================  =========================================================
Template and responsive markup  deterministic ``ImageAssetFactory`` fixture
Application upload action       mock ``ImageProcessorInterface``
Codec and geometry              real GD/Intervention processor with a tiny fixture
Storage paths and cleanup       ``LocalStorage`` under a temporary directory
Flysystem adapter               in-memory Flysystem adapter plus contract assertions
Doctrine mapping                kernel test with the application's real database platform
Complete user path              browser test with one small raster fixture
==============================  =========================================================

Deterministic ``ImageAsset`` fixtures
-------------------------------------

Use the shipped factory when a test needs realistic variants but not encoded files:

.. code-block:: php

    // tests/Controller/ProductControllerTest.php
    namespace App\Tests\Controller;

    use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
    use Symfony\UX\Image\Test\ImageAssetFactory;

    final class ProductControllerTest extends WebTestCase
    {
        public function testProductPageRendersResponsiveImage(): void
        {
            $product = ProductFactory::createOne([
                'image' => ImageAssetFactory::responsive(
                    storageName: 'products',
                    path: '/fixtures/product.jpeg',
                    formats: ['webp', 'jpeg'],
                    widths: [320, 640],
                ),
            ]);

            $crawler = self::createClient()->request('GET', '/products/'.$product->getId());

            self::assertCount(1, $crawler->filter('picture'));
            self::assertCount(2, $crawler->filter('picture source'));
            self::assertStringContainsString('640w', $crawler->filter('picture')->html());
        }
    }

The factory emits the same versioned ``format => list<variant>`` structure as the processor. It deliberately creates no files and performs no I/O.

Unit-test application code with the processor interface
-------------------------------------------------------

Do not construct GD in a controller unit test. Return a deterministic asset from the
public interface:

.. code-block:: php

    // tests/Controller/ProductImageControllerTest.php
    namespace App\Tests\Controller;

    use PHPUnit\Framework\TestCase;
    use Symfony\Component\HttpFoundation\File\UploadedFile;
    use Symfony\UX\Image\Processor\ImageProcessorInterface;
    use Symfony\UX\Image\Test\ImageAssetFactory;

    final class ProductImageControllerTest extends TestCase
    {
        public function testTheProcessedAssetIsAttachedToTheProduct(): void
        {
            $asset = ImageAssetFactory::responsive(storageName: 'products');
            $processor = $this->createMock(ImageProcessorInterface::class);
            $processor
                ->expects(self::once())
                ->method('process')
                ->with(
                    self::isInstanceOf(UploadedFile::class),
                    'product',
                    'products',
                )
                ->willReturn($asset);

            // Invoke the application service/controller, then assert that its owner
            // now references the same immutable $asset.
        }
    }

Mock the interface at the application boundary. Processor implementations also expose lower-level ``resize()`` and ``convert()`` methods, but application tests normally should not depend on those details.

Unit-test rendering without Symfony
-----------------------------------

``DefaultImageRenderer`` needs only a URL generator. A tiny fake keeps the test free of the container:

.. code-block:: php

    // tests/Image/ProductImageRendererTest.php
    namespace App\Tests\Image;

    use PHPUnit\Framework\TestCase;
    use Symfony\UX\Image\ImageAsset;
    use Symfony\UX\Image\Renderer\DefaultImageRenderer;
    use Symfony\UX\Image\Renderer\ImageRenderOptions;
    use Symfony\UX\Image\Test\ImageAssetFactory;
    use Symfony\UX\Image\UrlGenerator\UrlGeneratorInterface;

    final class ProductImageRendererTest extends TestCase
    {
        public function testItRendersAnAccessibleResponsiveImage(): void
        {
            $urls = new class implements UrlGeneratorInterface {
                public function generateAssetUrl(ImageAsset $asset): string
                {
                    return '/media'.$asset->getPath();
                }

                public function generateVariantUrl(ImageAsset $asset, array $variant): string
                {
                    return '/media'.$variant['path'];
                }
            };

            $html = (new DefaultImageRenderer($urls))->render(
                ImageAssetFactory::responsive(),
                new ImageRenderOptions(alt: 'Blue running shoe'),
            )->toHtml();

            self::assertStringContainsString('<picture>', $html);
            self::assertStringContainsString('type="image/webp"', $html);
            self::assertStringContainsString('alt="Blue running shoe"', $html);
            self::assertStringContainsString('loading="lazy"', $html);
        }
    }

Integration-test real processing
--------------------------------

Use a real temporary directory and a tiny valid image. Let the Symfony container build
the configured processor so the test covers profiles, capabilities, limits and storage
wiring:

.. code-block:: php

    // tests/Image/ImagePipelineTest.php
    namespace App\Tests\Image;

    use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
    use Symfony\Component\HttpFoundation\File\UploadedFile;
    use Symfony\UX\Image\Processor\ImageProcessorInterface;
    use Symfony\UX\Image\Storage\StorageInterface;

    final class ImagePipelineTest extends KernelTestCase
    {
        public function testItProcessesAndStoresTheConfiguredProfile(): void
        {
            self::bootKernel();
            $fixture = __DIR__.'/../Fixtures/photo.jpeg';
            $processor = self::getContainer()->get('test.image_processor');
            $storage = self::getContainer()->get('test.image_storage');

            self::assertInstanceOf(ImageProcessorInterface::class, $processor);
            self::assertInstanceOf(StorageInterface::class, $storage);

            $asset = $processor->process(
                new UploadedFile($fixture, 'photo.jpeg', 'image/jpeg', null, true),
                profile: 'product',
                storage: 'test_images',
            );

            self::assertSame('product', $asset->getProfile());
            self::assertNotEmpty($asset->getVariants());
            self::assertTrue($storage->exists($asset));
        }
    }

Expose test-only public aliases when the application keeps these autowiring contracts
private:

.. code-block:: yaml

    # config/services_test.yaml
    services:
        test.image_processor:
            alias: Symfony\UX\Image\Processor\ImageProcessorInterface
            public: true

        test.image_storage:
            alias: Symfony\UX\Image\Storage\StorageInterface
            public: true

The service IDs retrieved by the test now exactly match the public aliases. Keep these aliases in the ``test`` environment only.

Package documentation gates
---------------------------

The bundle's own suite keeps the published documentation and renderer output executable:

.. code-block:: terminal

    $ vendor/bin/phpunit tests/Documentation

This gate verifies that the complete RST corpus and diagram remain under the
configured ``doc_dir``, that every local README link and RST document reference
resolves inside that directory, and that both documented responsive HTML
fixtures still match ``DefaultImageRenderer``.

Test storage without cloud credentials
--------------------------------------

For the built-in local backend, construct ``LocalStorage`` under a test-owned directory:

.. code-block:: php

    // tests/Image/LocalImageStorageTest.php
    use Symfony\Component\HttpFoundation\File\UploadedFile;
    use Symfony\UX\Image\ImageAsset;
    use Symfony\UX\Image\Storage\LocalStorage;

    $root = sys_get_temp_dir().'/app-image-test-'.bin2hex(random_bytes(6));
    $storage = new LocalStorage(
        storages: ['test_images' => ['public_url_prefix' => '/test-media']],
        storageRoot: $root,
    );

    $path = $storage->store(
        new UploadedFile($fixture, 'unsafe.php', 'text/plain', null, true),
        'test_images',
        'products',
    );

    self::assertStringStartsWith('/products/', $path);
    self::assertStringEndsWith('.jpeg', $path);
    self::assertTrue($storage->exists(new ImageAsset('test_images', $path)));

Always remove the temporary root in ``tearDown()``. This test intentionally gives the file a hostile name and MIME declaration: the resulting extension must come from inspected bytes.

For Flysystem, use the same adapter contract in memory. Keep one provider-backed test in
a separate integration suite if signed URLs, bucket policy or object metadata are part
of the production contract.

Test Doctrine round trips
-------------------------

Enable ``doctrine_type: true``, persist an entity using the real mapped column, clear the entity manager, then compare the complete arrays:

.. code-block:: php

    $before = ImageAssetFactory::responsive(storageName: 'products');
    $product->setImage($before);
    $entityManager->persist($product);
    $entityManager->flush();
    $entityManager->clear();

    $after = $productRepository->find($product->getId())->getImage();

    self::assertSame($before->toArray(), $after->toArray());

Also insert malformed JSON in a focused DBAL test. Corrupt or unsupported asset schemas must fail conversion; they are never silently converted to ``null``.

What to assert in a browser test
--------------------------------

- the upload rejects a non-image and an over-budget image;
- a successful action persists the returned asset once;
- ``<picture>`` contains the expected format order and media conditions;
- ``<img>`` has meaningful ``alt``, stable dimensions and the intended loading priority;
- every generated URL is reachable;
- replacing or deleting an owner applies the application's cleanup policy;
- asynchronous UI distinguishes pending, success and recoverable failure.

Keep fixtures small. Test one codec per integration test, then use capability- gated
cases for WebP and AVIF so developer machines fail with an explicit skip reason rather
than a misleading assertion.
