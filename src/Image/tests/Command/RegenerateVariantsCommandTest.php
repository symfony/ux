<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Image\Command\RegenerateVariantsCommand;
use Symfony\UX\Image\Exception\ExceptionInterface;
use Symfony\UX\Image\Exception\RuntimeException;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\Processor\ImageProcessorInterface;
use Symfony\UX\Image\Regeneration\ImageAssetBatch;
use Symfony\UX\Image\Regeneration\ImageAssetBatchQuery;
use Symfony\UX\Image\Regeneration\ImageAssetPersisterInterface;
use Symfony\UX\Image\Regeneration\ImageAssetProviderInterface;
use Symfony\UX\Image\Regeneration\ImageAssetReference;
use Symfony\UX\Image\Regeneration\RegenerationServiceResolver;
use Symfony\UX\Image\Storage\StoragePath;
use Symfony\UX\Image\Storage\StreamStorageInterface;

#[CoversClass(RegenerateVariantsCommand::class)]
#[CoversClass(ImageAssetBatch::class)]
#[CoversClass(ImageAssetBatchQuery::class)]
#[CoversClass(ImageAssetReference::class)]
#[CoversClass(RegenerationServiceResolver::class)]
final class RegenerateVariantsCommandTest extends TestCase
{
    public function testProcessesTwoBoundedBatchesAndPersistsUpdatedAssets(): void
    {
        $provider = new RecordingProvider(static fn (ImageAssetBatchQuery $query): ImageAssetBatch => match ($query->after) {
            null => new ImageAssetBatch([
                self::reference('one', 'item-1'),
                self::reference('two', 'item-2'),
            ], 'batch-1'),
            'batch-1' => new ImageAssetBatch([
                self::reference('three', 'item-3'),
            ], null),
            default => throw new \LogicException('Unexpected cursor'),
        });
        $persister = new RecordingPersister();
        $processor = new RecordingProcessor();
        $tester = $this->tester($provider, $persister, $processor);

        $status = $tester->execute(['--image-profile' => 'avatar', '--storage' => 'media', '--batch-size' => '2']);

        self::assertSame(Command::SUCCESS, $status);
        self::assertCount(2, $provider->queries);
        self::assertSame('batch-1', $provider->queries[1]->after);
        self::assertCount(3, $processor->calls);
        self::assertCount(3, $persister->assets);
        self::assertSame('avatar', $persister->assets[0]->profile);
        self::assertNotNull($persister->assets[0]->profileRevision);
        self::assertSame(['webp' => [['path' => '/one_thumb.webp', 'width' => 100]]], $persister->assets[0]->variants);
        self::assertStringContainsString('checkpoint item-3', $tester->getDisplay());
        self::assertStringContainsString('atomically persisted', $tester->getDisplay());
    }

    public function testDryRunTraversesAllBatchesWithoutProcessingOrPersisting(): void
    {
        $provider = new RecordingProvider(static fn (ImageAssetBatchQuery $query): ImageAssetBatch => null === $query->after
            ? new ImageAssetBatch([self::reference('one', 'one-cursor')], 'next')
            : new ImageAssetBatch([self::reference('two', 'two-cursor')], null));
        $persister = new RecordingPersister();
        $processor = new RecordingProcessor();
        $tester = $this->tester($provider, $persister, $processor);

        $status = $tester->execute(['--image-profile' => 'avatar', '--storage' => 'media', '--dry-run' => true]);

        self::assertSame(Command::SUCCESS, $status);
        self::assertCount(2, $provider->queries);
        self::assertSame([], $processor->calls);
        self::assertSame([], $persister->assets);
        self::assertStringContainsString('nothing was processed', $tester->getDisplay());
        self::assertStringContainsString('or persisted', $tester->getDisplay());
    }

    public function testPersistenceFailureStopsAtLastDurableCheckpoint(): void
    {
        $provider = new RecordingProvider(static fn (): ImageAssetBatch => new ImageAssetBatch([
            self::reference('one', 'cursor-1'),
            self::reference('two', 'cursor-2'),
        ], null));
        $persister = new RecordingPersister(failOnId: 'two');
        $tester = $this->tester($provider, $persister, new RecordingProcessor());

        $status = $tester->execute(['--image-profile' => 'avatar', '--storage' => 'media']);

        self::assertSame(Command::FAILURE, $status);
        self::assertCount(1, $persister->assets);
        self::assertStringContainsString('after cursor "cursor-1"', $tester->getDisplay());
        self::assertStringContainsString("--after='cursor-1'", $tester->getDisplay());
    }

    public function testConcurrentUpdateDiscardsOnlyNewGenerationAndKeepsCheckpoint(): void
    {
        $provider = new RecordingProvider(static fn (): ImageAssetBatch => new ImageAssetBatch([
            self::reference('one', 'cursor-1'),
        ], null));
        $persister = new RecordingPersister(conflictOnId: 'one');
        $storage = $this->createMock(StreamStorageInterface::class);
        $storage->expects(self::once())->method('deletePath')
            ->with('media', self::callback(static fn (StoragePath $path): bool => 'one_thumb.webp' === $path->value));
        $tester = $this->tester($provider, $persister, new RecordingProcessor(), $storage);

        $status = $tester->execute(['--image-profile' => 'avatar', '--storage' => 'media']);

        self::assertSame(Command::FAILURE, $status);
        self::assertStringContainsString('changed concurrently', $tester->getDisplay());
        self::assertStringContainsString('without --after', $tester->getDisplay());
    }

    public function testProviderFailureAfterBatchPrintsDurableCheckpoint(): void
    {
        $provider = new RecordingProvider(static fn (ImageAssetBatchQuery $query): ImageAssetBatch => null === $query->after
            ? new ImageAssetBatch([self::reference('one', 'cursor-1')], 'next')
            : throw new \RuntimeException('provider unavailable'));
        $tester = $this->tester($provider, new RecordingPersister(), new RecordingProcessor());

        $status = $tester->execute(['--image-profile' => 'avatar', '--storage' => 'media']);

        self::assertSame(Command::FAILURE, $status);
        self::assertStringContainsString("--after='next'", $tester->getDisplay());
    }

    public function testProviderFailureReturnsFailureWithoutProcessing(): void
    {
        $provider = new RecordingProvider(static function (): never {
            throw new \RuntimeException('query failed');
        });
        $processor = new RecordingProcessor();
        $tester = $this->tester($provider, new RecordingPersister(), $processor);

        $status = $tester->execute(['--image-profile' => 'avatar', '--storage' => 'media']);

        self::assertSame(Command::FAILURE, $status);
        self::assertSame([], $processor->calls);
        self::assertStringContainsString('Provider failed: query failed', $tester->getDisplay());
    }

    public function testCurrentRevisionIsSkippedUnlessForced(): void
    {
        $config = self::profiles()['avatar'];
        $revision = hash('sha256', json_encode($config, \JSON_THROW_ON_ERROR));
        $asset = new ImageAsset('media', '/media/current.jpeg', variants: [
            'webp' => [['name' => 'thumb', 'path' => '/media/current_thumb.webp', 'width' => 100]],
        ], profile: 'avatar', profileRevision: $revision);
        $provider = new RecordingProvider(static fn (): ImageAssetBatch => new ImageAssetBatch([
            new ImageAssetReference('current', 'cursor', 'version-current', $asset),
        ], null));
        $persister = new RecordingPersister();
        $processor = new RecordingProcessor();

        $tester = $this->tester($provider, $persister, $processor);
        self::assertSame(Command::SUCCESS, $tester->execute(['--image-profile' => 'avatar', '--storage' => 'media']));
        self::assertSame([], $processor->calls);

        $tester = $this->tester($provider, $persister, $processor);
        self::assertSame(Command::SUCCESS, $tester->execute(['--image-profile' => 'avatar', '--storage' => 'media', '--force' => true]));
        self::assertCount(1, $processor->calls);
    }

    public function testCurrentRevisionWithoutGeneratedVariantsIsRegenerated(): void
    {
        $config = self::profiles()['avatar'];
        $revision = hash('sha256', json_encode($config, \JSON_THROW_ON_ERROR));
        $asset = new ImageAsset('media', '/media/deferred.jpeg', profile: 'avatar', profileRevision: $revision);
        $provider = new RecordingProvider(static fn (): ImageAssetBatch => new ImageAssetBatch([
            new ImageAssetReference('deferred', 'cursor', 'version-current', $asset),
        ], null));
        $processor = new RecordingProcessor();

        $tester = $this->tester($provider, new RecordingPersister(), $processor);

        self::assertSame(Command::SUCCESS, $tester->execute(['--image-profile' => 'avatar', '--storage' => 'media']));
        self::assertCount(1, $processor->calls);
    }

    public function testInvalidOptionsAndUnknownProfileReturnInvalid(): void
    {
        $tester = $this->tester(new RecordingProvider(static fn (): ImageAssetBatch => new ImageAssetBatch([], null)), new RecordingPersister(), new RecordingProcessor());

        self::assertSame(Command::INVALID, $tester->execute([]));
        self::assertSame(Command::INVALID, $tester->execute(['--image-profile' => 'missing', '--storage' => 'media']));
        self::assertSame(Command::INVALID, $tester->execute(['--image-profile' => 'avatar', '--storage' => 'media', '--batch-size' => '0']));
        self::assertSame(Command::INVALID, $tester->execute(['--image-profile' => 'avatar', '--storage' => '../outside']));
        self::assertSame(Command::INVALID, $tester->execute(['--image-profile' => 'avatar', '--storage' => 'unknown']));
        self::assertSame(Command::INVALID, $tester->execute(['--image-profile' => 'avatar', '--storage' => 'media', '--after' => '']));
    }

    public function testRejectsOversizedProviderBatch(): void
    {
        $items = [];
        for ($index = 0; $index < 101; ++$index) {
            $items[] = self::reference('asset-'.$index, 'cursor-'.$index);
        }
        $tester = $this->tester(
            new RecordingProvider(static fn (): ImageAssetBatch => new ImageAssetBatch($items, null)),
            new RecordingPersister(),
            new RecordingProcessor(),
        );

        self::assertSame(Command::FAILURE, $tester->execute(['--image-profile' => 'avatar', '--storage' => 'media', '--batch-size' => 100]));
        self::assertStringContainsString('returned 101 assets', $tester->getDisplay());
    }

    public function testRejectsAssetFromAnotherStorage(): void
    {
        $reference = new ImageAssetReference('one', 'cursor', 'version', new ImageAsset('other', '/one.jpeg'));
        $tester = $this->tester(
            new RecordingProvider(static fn (): ImageAssetBatch => new ImageAssetBatch([$reference], null)),
            new RecordingPersister(),
            new RecordingProcessor(),
        );

        self::assertSame(Command::FAILURE, $tester->execute(['--image-profile' => 'avatar', '--storage' => 'media']));
        self::assertStringContainsString('expected "media"', $tester->getDisplay());
    }

    public function testRejectsNonProgressingProviderCursor(): void
    {
        $tester = $this->tester(
            new RecordingProvider(static fn (): ImageAssetBatch => new ImageAssetBatch([self::reference('one', 'cursor-one')], 'same')),
            new RecordingPersister(),
            new RecordingProcessor(),
        );

        self::assertSame(Command::FAILURE, $tester->execute(['--image-profile' => 'avatar', '--storage' => 'media', '--after' => 'same']));
        self::assertStringContainsString('non-progressing cursor', $tester->getDisplay());
    }

    public function testBatchRejectsEmptyOrDuplicateCursors(): void
    {
        try {
            new ImageAssetBatch([self::reference('one', 'cursor')], '');
            self::fail('An empty next cursor must be rejected.');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('cannot be empty', $e->getMessage());
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('duplicate');
        new ImageAssetBatch([
            self::reference('one', 'cursor'),
            self::reference('two', 'cursor'),
        ], null);
    }

    public function testRegenerationValueObjectsRejectInvalidInput(): void
    {
        try {
            new ImageAssetBatch([], 'next');
            self::fail('An empty batch must not expose a cursor.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('empty image asset batch', $exception->getMessage());
        }

        try {
            new ImageAssetBatchQuery('', 'media', 100);
            self::fail('A query must require a profile.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('requires profile', $exception->getMessage());
        }

        $this->expectException(\InvalidArgumentException::class);
        new ImageAssetReference('', 'cursor', 'version', new ImageAsset('media', '/one.jpeg'));
    }

    public function testRegenerationValueObjectsExposeTheirProtocolThroughGetters(): void
    {
        $asset = new ImageAsset('media', '/one.jpeg');
        $reference = new ImageAssetReference('one', 'cursor-1', 'version-1', $asset);
        $query = new ImageAssetBatchQuery('product', 'media', 100, 'cursor-0');
        $batch = new ImageAssetBatch([$reference], 'cursor-1');

        self::assertSame('product', $query->getProfile());
        self::assertSame('media', $query->getStorage());
        self::assertSame(100, $query->getLimit());
        self::assertSame('cursor-0', $query->getAfter());
        self::assertSame('one', $reference->getId());
        self::assertSame('cursor-1', $reference->getCursor());
        self::assertSame('version-1', $reference->getVersion());
        self::assertSame($asset, $reference->getAsset());
        self::assertSame([$reference], $batch->getItems());
        self::assertSame('cursor-1', $batch->getNextCursor());
    }

    public function testZeroOrMultipleServicesReturnInvalidWithActionableError(): void
    {
        $processor = new RecordingProcessor();
        $storage = $this->createStub(StreamStorageInterface::class);
        $zero = new CommandTester(new RegenerateVariantsCommand($processor, self::profiles(), new RegenerationServiceResolver([], []), $storage, ['media' => []]));
        self::assertSame(Command::INVALID, $zero->execute(['--image-profile' => 'avatar', '--storage' => 'media']));
        self::assertStringContainsString('exactly one ImageAssetProviderInterface', $zero->getDisplay());

        $provider = new RecordingProvider(static fn (): ImageAssetBatch => new ImageAssetBatch([], null));
        $multiple = new CommandTester(new RegenerateVariantsCommand(
            $processor,
            self::profiles(),
            new RegenerationServiceResolver([$provider, $provider], [new RecordingPersister()]),
            $storage,
            ['media' => []],
        ));
        self::assertSame(Command::INVALID, $multiple->execute(['--image-profile' => 'avatar', '--storage' => 'media']));
        self::assertStringContainsString('found 2', $multiple->getDisplay());
    }

    public function testRegenerationServiceResolutionFailureImplementsPackageExceptionMarker(): void
    {
        $resolver = new RegenerationServiceResolver([], []);

        try {
            $resolver->provider();
            self::fail('A missing provider must be rejected.');
        } catch (ExceptionInterface $e) {
            self::assertInstanceOf(RuntimeException::class, $e);
            self::assertStringContainsString('exactly one ImageAssetProviderInterface', $e->getMessage());
        }
    }

    public function testMissingPersisterIsRejected(): void
    {
        $this->expectException(RuntimeException::class);

        new RegenerationServiceResolver([], [])->persister();
    }

    private function tester(RecordingProvider $provider, RecordingPersister $persister, RecordingProcessor $processor, ?StreamStorageInterface $storage = null): CommandTester
    {
        $storage ??= $this->createStub(StreamStorageInterface::class);

        return new CommandTester(new RegenerateVariantsCommand(
            $processor,
            self::profiles(),
            new RegenerationServiceResolver([$provider], [$persister]),
            $storage,
            ['media' => []],
        ));
    }

    private static function reference(string $id, string $cursor): ImageAssetReference
    {
        return new ImageAssetReference($id, $cursor, 'version-'.$id, new ImageAsset('media', '/'.$id.'.jpeg', mimeType: 'image/jpeg', width: 800, height: 600));
    }

    /** @return array<string, array<string, mixed>> */
    private static function profiles(): array
    {
        return ['avatar' => ['formats' => ['webp'], 'variants' => ['thumb' => ['width' => 100]]]];
    }
}

final class RecordingProvider implements ImageAssetProviderInterface
{
    /** @var list<ImageAssetBatchQuery> */
    public array $queries = [];

    /** @param callable(ImageAssetBatchQuery): ImageAssetBatch $fetch */
    public function __construct(private $fetch)
    {
    }

    public function fetch(ImageAssetBatchQuery $query): ImageAssetBatch
    {
        $this->queries[] = $query;

        return ($this->fetch)($query);
    }
}

final class RecordingPersister implements ImageAssetPersisterInterface
{
    /** @var list<ImageAsset> */
    public array $assets = [];

    public function __construct(
        private readonly ?string $failOnId = null,
        private readonly ?string $conflictOnId = null,
    ) {
    }

    public function compareAndSwap(ImageAssetReference $reference, ImageAsset $asset): bool
    {
        if ($reference->id === $this->failOnId) {
            throw new \RuntimeException('database unavailable');
        }
        if ($reference->id === $this->conflictOnId) {
            return false;
        }
        $this->assets[] = $asset;

        return true;
    }
}

final class RecordingProcessor implements ImageProcessorInterface
{
    /** @var list<ImageAsset> */
    public array $calls = [];

    public function supports(string $driver): bool
    {
        return true;
    }

    public function process(UploadedFile $file, ?string $profile = null, string $storage = 'default_public'): ImageAsset
    {
        throw new \BadMethodCallException();
    }

    public function generateVariants(ImageAsset $imageAsset, array $variantConfigs): array
    {
        $this->calls[] = $imageAsset;

        return ['webp' => [['path' => preg_replace('/\\.jpeg$/', '_thumb.webp', $imageAsset->path), 'width' => 100]]];
    }

    public function resize(string $inputPath, string $outputPath, int $width, int $height, string $mode = 'fit', string $position = 'center'): void
    {
    }

    public function convert(string $inputPath, string $outputPath, string $format, int $quality = 80): void
    {
    }

    public function extractMetadata(UploadedFile $file): array
    {
        return ['width' => null, 'height' => null, 'mime' => null];
    }
}
