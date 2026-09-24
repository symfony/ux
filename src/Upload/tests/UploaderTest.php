<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests;

use League\Flysystem\Filesystem as FlysystemFilesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Upload\Event\UploadAssembledEvent;
use Symfony\UX\Upload\Exception\InvalidArgumentException;
use Symfony\UX\Upload\Exception\ValidationException;
use Symfony\UX\Upload\Policy\UploadPolicy;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Storage\FlysystemStorage;
use Symfony\UX\Upload\Storage\InMemoryStorage;
use Symfony\UX\Upload\Storage\LocalStorage;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Upload\PendingUpload;
use Symfony\UX\Upload\Upload\UploadedFile;
use Symfony\UX\Upload\Upload\UploadProgress;
use Symfony\UX\Upload\Uploader;
use Symfony\UX\Upload\Url\UploadUrlGeneratorInterface;

#[CoversClass(Uploader::class)]
final class UploaderTest extends TestCase
{
    private Uploader $uploader;
    private InMemoryStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new InMemoryStorage();
        $dispatcher = new EventDispatcher();

        $urlGenerator = $this->createStub(UploadUrlGeneratorInterface::class);
        $urlGenerator->method('generateUploadUrl')->willReturn('http://example.com/upload/123');

        $this->uploader = new Uploader(
            $this->storage,
            $urlGenerator,
            $dispatcher,
            chunkSize: 5 * 1024 * 1024,
            parallelChunks: 3,
            compressionEnabled: true,
        );
    }

    protected function tearDown(): void
    {
        NativeFunctions::reset();
    }

    public function testInitializeUpload(): void
    {
        $result = $this->uploader->initializeUpload('test.txt', 1000, 'text/plain');

        self::assertInstanceOf(PendingUpload::class, $result);
        self::assertNotEmpty($result->uploadId);
        self::assertSame('test.txt', $result->filename);
        self::assertSame(1000, $result->fileSize);
        self::assertSame('text/plain', $result->mimeType);
        self::assertSame(1, $result->totalChunks);
        self::assertSame(5 * 1024 * 1024, $result->chunkSize);
        self::assertTrue($result->compression);
        self::assertSame(3, $result->parallel);
    }

    public function testDirectUploadCompletesWithExactBytesAndMetadata(): void
    {
        $content = "direct upload\0bytes";
        $context = new UploadContext('owner-1', 'tenant-1', 'profile.document');
        $policy = new UploadPolicy('default', 1024, ['text/plain'], 1, time() + 60, 'profile.document');

        $completed = $this->uploader->uploadDirect(
            'document.txt',
            \strlen($content),
            'text/plain',
            $content,
            hash('sha256', $content),
            'sha256',
            hash('sha256', $content),
            $context,
            $policy,
        );

        self::assertSame('document.txt', $completed->getClientOriginalName());
        self::assertSame('text/plain', $completed->getMimeType());
        self::assertSame(\strlen($content), $completed->getSize());
        self::assertSame(hash('sha256', $content), $completed->getChecksum());
        self::assertSame('owner-1', $completed->getOwnerId());
        self::assertSame('tenant-1', $completed->getTenantId());
        self::assertSame('profile.document', $completed->getFieldName());
        self::assertSame($content, $this->storage->read($completed->getStoragePath()));
    }

    public function testDirectUploadDecompressesBeforeDigestAndCompletion(): void
    {
        $content = str_repeat('compressible data ', 20);
        $compressed = gzencode($content);
        self::assertIsString($compressed);

        $completed = $this->uploader->uploadDirect(
            'compressed.txt',
            \strlen($content),
            'text/plain',
            $compressed,
            digest: hash('sha256', $content),
            compressed: true,
        );

        self::assertSame($content, $this->storage->read($completed->getStoragePath()));
    }

    public function testDirectUploadRejectsOversizeBeforeCreatingSession(): void
    {
        $dispatcher = $this->createMock(\Psr\EventDispatcher\EventDispatcherInterface::class);
        $dispatcher->expects(self::never())->method('dispatch');
        $uploader = new Uploader(
            $this->storage,
            $this->createStub(UploadUrlGeneratorInterface::class),
            $dispatcher,
            chunkSize: 4,
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('exceeds the single-request limit');

        $uploader->uploadDirect('large.txt', 5, 'text/plain', '12345');
    }

    public function testDirectUploadCleansNonResumableSessionAfterFailure(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(UploadAssembledEvent::class, static function (): never {
            throw new \RuntimeException('scanner unavailable');
        });
        $uploader = new Uploader(
            $this->storage,
            $this->createStub(UploadUrlGeneratorInterface::class),
            $dispatcher,
        );

        try {
            $uploader->uploadDirect('failed.txt', 4, 'text/plain', 'data');
            self::fail('Expected direct completion to fail.');
        } catch (\RuntimeException $e) {
            self::assertSame('scanner unavailable', $e->getMessage());
        }

        self::assertSame(0, $this->storage->countPendingByContext(new UploadContext()));
    }

    public function testDirectUploadPreservesOriginalFailureWhenCleanupAlsoFails(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('getMetadata')->willReturn([
            'filename' => 'failed.txt',
            'fileSize' => 4,
            'mimeType' => 'text/plain',
            'totalChunks' => 1,
        ]);
        $storage->method('abort')->willThrowException(new \RuntimeException('cleanup failed'));
        $uploader = new Uploader(
            $storage,
            $this->createStub(UploadUrlGeneratorInterface::class),
            new EventDispatcher(),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Integrity check failed for chunk 0.');

        $uploader->uploadDirect('failed.txt', 4, 'text/plain', 'data', digest: hash('sha256', 'different'));
    }

    public function testDirectUploadProducesExactBytesWithLocalAndFlysystemStorage(): void
    {
        $root = sys_get_temp_dir().'/ux_upload_direct_'.bin2hex(random_bytes(8));
        $filesystem = new Filesystem();

        try {
            $storages = [
                new LocalStorage($root.'/local/files', $root.'/local/pending'),
                new FlysystemStorage(
                    new FlysystemFilesystem(new LocalFilesystemAdapter($root.'/flysystem')),
                    $root.'/assembly',
                ),
            ];

            foreach ($storages as $storage) {
                $uploader = new Uploader(
                    $storage,
                    $this->createStub(UploadUrlGeneratorInterface::class),
                    new EventDispatcher(),
                    distributedLockGuaranteed: $storage instanceof FlysystemStorage,
                );
                $content = random_bytes(1024);

                $completed = $uploader->uploadDirect(
                    'bytes.bin',
                    \strlen($content),
                    'application/octet-stream',
                    $content,
                    hash: hash('sha256', $content),
                    digest: hash('sha256', $content),
                );
                $stream = $completed->openStream();
                try {
                    self::assertSame($content, stream_get_contents($stream));
                } finally {
                    fclose($stream);
                }
            }
        } finally {
            $filesystem->remove($root);
        }
    }

    public function testRejectsExcessiveChunkSizeAtRuntime(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('chunk size must be between');

        new Uploader(
            $this->storage,
            $this->createStub(UploadUrlGeneratorInterface::class),
            new EventDispatcher(),
            chunkSize: Uploader::MAX_CHUNK_SIZE + 1,
        );
    }

    public function testRejectsInvalidConstructorConfiguration(): void
    {
        /** @var list<array<string, mixed>> $invalidArguments */
        $invalidArguments = [
            ['name' => ''],
            ['parallelChunks' => 0],
            ['maxSize' => -1],
            ['completedTtl' => 59],
            ['maxPendingPerOwner' => 0],
            ['integrityAlgorithm' => 'md5'],
        ];

        foreach ($invalidArguments as $arguments) {
            try {
                new Uploader(
                    $this->storage,
                    $this->createStub(UploadUrlGeneratorInterface::class),
                    new EventDispatcher(),
                    ...$arguments,
                );
                self::fail('Expected invalid uploader configuration to be rejected.');
            } catch (InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testInitializeRejectsMismatchedHashAlgorithm(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('does not match configured integrity algorithm');

        $this->uploader->initializeUpload('test.txt', 4, 'text/plain', hash('sha512', 'data'), 'sha512');
    }

    public function testDistributedStorageRequiresSharedLockGuarantee(): void
    {
        $storage = new FlysystemStorage($this->createStub(FilesystemOperator::class));
        $uploader = new Uploader($storage, $this->createStub(UploadUrlGeneratorInterface::class), new EventDispatcher());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('requires a configured shared lock');

        $uploader->initializeUpload('test.txt', 4, 'text/plain');
    }

    public function testLocalStorageEnforcesQuotaAndCompletesThroughVerifiedAssembly(): void
    {
        $root = sys_get_temp_dir().'/ux_upload_uploader_'.bin2hex(random_bytes(8));
        $filesystem = new Filesystem();

        try {
            $storage = new LocalStorage($root.'/files', $root.'/pending');
            $uploader = new Uploader(
                $storage,
                $this->createStub(UploadUrlGeneratorInterface::class),
                new EventDispatcher(),
                chunkSize: 4,
                maxPendingPerOwner: 1,
            );
            $pending = $uploader->initializeUpload('test.txt', 4, 'text/plain');

            try {
                $uploader->initializeUpload('other.txt', 4, 'text/plain');
                self::fail('Expected the pending upload quota to be enforced.');
            } catch (ValidationException $e) {
                self::assertStringContainsString('Maximum number of 1 pending uploads', $e->getMessage());
            }

            $uploader->storeChunk($pending->uploadId, 0, 'data');
            $completed = $uploader->completeUpload($pending->uploadId);

            self::assertSame(4, $completed->getSize());
            self::assertTrue($storage->exists($completed->getStoragePath()));
        } finally {
            $filesystem->remove($root);
        }
    }

    public function testStoreChunkRejectsPayloadLargerThanConfiguredChunkSize(): void
    {
        $uploader = new Uploader(
            $this->storage,
            $this->createStub(UploadUrlGeneratorInterface::class),
            new EventDispatcher(),
            chunkSize: 4,
        );
        $pending = $uploader->initializeUpload('test.txt', 5, 'text/plain');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('exceeds maximum allowed chunk size');

        $uploader->storeChunk($pending->uploadId, 0, '12345');
    }

    public function testCompleteRejectsUnknownUpload(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $this->uploader->completeUpload('missing');
    }

    public function testCompleteRejectsInvalidHashAlgorithmMetadata(): void
    {
        $this->storage->initiate('id', [
            'filename' => 'test.txt',
            'fileSize' => 4,
            'mimeType' => 'text/plain',
            'totalChunks' => 1,
            'hash' => hash('sha256', 'data'),
            'hashAlgorithm' => [],
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid file hash algorithm metadata');

        $this->uploader->completeUpload('id');
    }

    public function testCompleteRejectsIncompleteCompletedMetadata(): void
    {
        $this->storage->initiate('id', ['completedPath' => 'completed/id']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Completed upload metadata "filename" is missing');

        $this->uploader->completeUpload('id');
    }

    public function testUploadIdIsUniqueAndPathSafeHex(): void
    {
        $seen = [];
        for ($i = 0; $i < 20; ++$i) {
            $uploadId = $this->uploader->initializeUpload('test.txt', 1000, 'text/plain')->uploadId;

            self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $uploadId);
            self::assertArrayNotHasKey($uploadId, $seen);
            $seen[$uploadId] = true;
        }
    }

    public function testOnlyDeclaredGzipChunksAreInflatedBeforeAssembly(): void
    {
        $uploader = new Uploader(
            $this->storage,
            $this->createStub(UploadUrlGeneratorInterface::class),
            new EventDispatcher(),
            chunkSize: 16,
            compressionEnabled: true,
        );
        $lookalike = "\x1f\x8b\x08\x00not-gzip-123";
        $declared = 'inflated content';
        $compressed = gzencode($declared);
        self::assertIsString($compressed);
        $pending = $uploader->initializeUpload('mixed.bin', \strlen($lookalike) + \strlen($declared), 'application/octet-stream');

        $uploader->storeChunk($pending->uploadId, 0, $lookalike);
        $uploader->storeChunk($pending->uploadId, 1, $compressed, compressed: true);
        $completed = $uploader->completeUpload($pending->uploadId);

        self::assertSame($lookalike.$declared, $this->storage->read($completed->getStoragePath()));
    }

    public function testStoreChunkVerifiesOptionalSha256Digest(): void
    {
        $upload = $this->uploader->initializeUpload('test.txt', 4, 'text/plain');

        $this->uploader->storeChunk($upload->uploadId, 0, 'data', hash('sha256', 'data'));
        self::assertSame([0], $this->storage->listChunks($upload->uploadId));

        $other = $this->uploader->initializeUpload('other.txt', 4, 'text/plain');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Integrity check failed for chunk 0');
        $this->uploader->storeChunk($other->uploadId, 0, 'data', hash('sha256', 'tampered'));
    }

    public function testStoreChunkAcceptsIdenticalRetransmissionWithoutDuplicating(): void
    {
        $result = $this->uploader->initializeUpload('test.txt', 1000, 'text/plain');
        $uploadId = $result->uploadId;

        $this->uploader->storeChunk($uploadId, 0, 'data');
        $this->uploader->storeChunk($uploadId, 0, 'data');

        self::assertSame([0], $this->storage->listChunks($uploadId));
    }

    public function testStoreChunkOverwriteAttemptPreservesOriginalData(): void
    {
        $result = $this->uploader->initializeUpload('test.txt', 8, 'text/plain');
        $uploadId = $result->uploadId;

        $this->uploader->storeChunk($uploadId, 0, 'original');

        try {
            $this->uploader->storeChunk($uploadId, 0, 'tampered');
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            self::assertStringContainsString('already been uploaded', $e->getMessage());
        }

        // The rejected overwrite must not add a duplicate or replace the stored chunk.
        self::assertSame([0], $this->storage->listChunks($uploadId));

        $path = $this->uploader->completeUpload($uploadId)->getStoragePath();
        self::assertSame('original', $this->storage->read($path));
    }

    public function testCompleteUploadCleansUpOnFailure(): void
    {
        // Force hash failure by initializing with a hash and then uploading different content
        $result = $this->uploader->initializeUpload('test.txt', 4, 'text/plain', 'invalid-hash');
        $uploadId = $result->uploadId;
        $this->uploader->storeChunk($uploadId, 0, 'data');

        try {
            $this->uploader->completeUpload($uploadId);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertStringContainsString('integrity check failed', $e->getMessage());
        }

        // Verify cleanup: metadata should be gone because abort was called in the catch block
        self::assertNull($this->storage->getMetadata($uploadId));
    }

    public function testTransientCompletionFailureCanRetryWithoutRetransmittingChunks(): void
    {
        $dispatcher = new EventDispatcher();
        $attempt = 0;
        $dispatcher->addListener(UploadAssembledEvent::class, static function () use (&$attempt): void {
            if (1 === ++$attempt) {
                throw new \RuntimeException('scanner unavailable');
            }
        });
        $uploader = new Uploader($this->storage, $this->createStub(UploadUrlGeneratorInterface::class), $dispatcher);
        $pending = $uploader->initializeUpload('retry.txt', 4, 'text/plain');
        $uploader->storeChunk($pending->uploadId, 0, 'data');

        try {
            $uploader->completeUpload($pending->uploadId);
            self::fail('The first completion must fail.');
        } catch (\RuntimeException $e) {
            self::assertSame('scanner unavailable', $e->getMessage());
        }
        self::assertNotNull($this->storage->getMetadata($pending->uploadId));
        self::assertSame([0], $this->storage->listChunks($pending->uploadId));

        $completed = $uploader->completeUpload($pending->uploadId);
        self::assertSame('retry.txt', $completed->getClientOriginalName());
    }

    public function testCompleteUpload(): void
    {
        $result = $this->uploader->initializeUpload('test.txt', 4, 'text/plain');
        $uploadId = $result->uploadId;

        $this->uploader->storeChunk($uploadId, 0, 'data');

        $uploadResult = $this->uploader->completeUpload($uploadId);

        self::assertInstanceOf(UploadedFile::class, $uploadResult);
        self::assertSame('test.txt', $uploadResult->getClientOriginalName());
        self::assertStringStartsWith('.tmp/completed/', $uploadResult->getStoragePath());
        self::assertSame('text/plain', $uploadResult->getMimeType());
        self::assertSame(4, $uploadResult->getSize());
        self::assertSame($uploadId, $uploadResult->getId());
    }

    public function testCompletedTtlStartsWhenAssemblyCompletes(): void
    {
        $pending = $this->uploader->initializeUpload('test.txt', 4, 'text/plain');
        $metadata = $this->storage->getMetadata($pending->uploadId);
        self::assertNotNull($metadata);
        self::assertArrayNotHasKey('expiresAt', $metadata);
        $this->uploader->storeChunk($pending->uploadId, 0, 'data');
        $completedAt = time();

        $completed = $this->uploader->completeUpload($pending->uploadId);

        self::assertGreaterThanOrEqual($completedAt + Uploader::DEFAULT_COMPLETED_TTL, $completed->getExpiresAt()->getTimestamp());
        self::assertLessThanOrEqual(time() + Uploader::DEFAULT_COMPLETED_TTL, $completed->getExpiresAt()->getTimestamp());
    }

    #[TestWith([0], 'zero')]
    #[TestWith([-100], 'negative')]
    public function testInitializeUploadRejectsANonPositiveSize(int $fileSize): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File size must be greater than zero');

        $this->uploader->initializeUpload('empty.txt', $fileSize, 'text/plain');
    }

    /**
     * @return iterable<string, array{int, int, string}>
     */
    public static function outOfRangeChunkIndexes(): iterable
    {
        yield 'negative index' => [1000, -1, 'Chunk index -1 is out of range [0, 1)'];
        yield 'index past the only chunk' => [1000, 5, 'Chunk index 5 is out of range [0, 1)'];
        yield 'index equal to the chunk count' => [15 * 1024 * 1024, 3, 'Chunk index 3 is out of range [0, 3)'];
    }

    #[DataProvider('outOfRangeChunkIndexes')]
    public function testStoreChunkRejectsAnOutOfRangeIndex(int $fileSize, int $index, string $message): void
    {
        $pending = $this->uploader->initializeUpload('test.txt', $fileSize, 'text/plain');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $this->uploader->storeChunk($pending->uploadId, $index, 'data');
    }

    public function testStoreChunkAcceptsValidIndex(): void
    {
        // 15MB file with 5MB chunk size = 3 total chunks
        $result = $this->uploader->initializeUpload('test.txt', 15 * 1024 * 1024, 'text/plain');
        self::assertSame(3, $result->totalChunks);

        // Index 0 of 3 should be valid
        $this->uploader->storeChunk($result->uploadId, 0, 'data');
        self::assertCount(1, $this->storage->listChunks($result->uploadId));

        // Index 2 of 3 should be valid (last chunk)
        $this->uploader->storeChunk($result->uploadId, 2, 'data');
        self::assertCount(2, $this->storage->listChunks($result->uploadId));
    }

    public function testStoreChunkRejectsDeclaredCompressionWhenDisabled(): void
    {
        $uploader = new Uploader(
            $this->storage,
            $this->createStub(UploadUrlGeneratorInterface::class),
            new EventDispatcher(),
        );
        $result = $uploader->initializeUpload('test.txt', 1000, 'text/plain');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('compression is not enabled');

        $chunk = gzencode('data');
        self::assertIsString($chunk);
        $uploader->storeChunk($result->uploadId, 0, $chunk, compressed: true);
    }

    public function testStoreChunkThrowsOnCorruptGzipData(): void
    {
        $result = $this->uploader->initializeUpload('test.txt', 1000, 'text/plain');

        // Declared compressed but the payload is not valid gzip
        $corruptGzipData = "\x1f\x8b\x00\x00\x00\x00\x00\x00corrupt-not-valid-gzip";

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Failed to decompress gzip chunk 0');

        $this->uploader->storeChunk($result->uploadId, 0, $corruptGzipData, compressed: true);
    }

    public function testStoreChunkRejectsDecompressedDataLargerThanChunkSize(): void
    {
        $uploader = new Uploader(
            $this->storage,
            $this->createStub(UploadUrlGeneratorInterface::class),
            new EventDispatcher(),
            chunkSize: 8,
            compressionEnabled: true,
        );
        $result = $uploader->initializeUpload('test.txt', 9, 'text/plain');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Decompressed chunk 0 size of 9 bytes exceeds maximum allowed chunk size of 8 bytes');

        $chunk = gzencode('123456789');
        self::assertIsString($chunk);
        $uploader->storeChunk($result->uploadId, 0, $chunk, compressed: true);
    }

    public function testCompleteUploadRejectsAssembledSizeMismatch(): void
    {
        $result = $this->uploader->initializeUpload('test.txt', 5, 'text/plain');
        $this->uploader->storeChunk($result->uploadId, 0, 'data');

        try {
            $this->uploader->completeUpload($result->uploadId);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame('Assembled file size 4 bytes does not match declared file size of 5 bytes.', $e->getMessage());
        }

        self::assertNull($this->storage->getMetadata($result->uploadId));
        self::assertFalse($this->storage->exists('test/test.txt'));
    }

    public function testGetProgressReturnsCorrectPercentage(): void
    {
        $result = $this->uploader->initializeUpload('test.txt', 15 * 1024 * 1024, 'text/plain');
        $uploadId = $result->uploadId;

        // With 5MB chunk size, 15MB file should have 3 chunks
        self::assertSame(3, $result->totalChunks);

        // Store 1 of 3 chunks
        $this->uploader->storeChunk($uploadId, 0, 'data');
        $progress = $this->uploader->getProgress($uploadId);
        self::assertInstanceOf(UploadProgress::class, $progress);
        self::assertSame($uploadId, $progress->uploadId);
        self::assertSame(1, $progress->storedChunks);
        self::assertSame(3, $progress->totalChunks);
        self::assertSame(33, $progress->percentComplete);

        // Store 2 of 3 chunks
        $this->uploader->storeChunk($uploadId, 1, 'data');
        $progress = $this->uploader->getProgress($uploadId);
        self::assertSame(2, $progress->storedChunks);
        self::assertSame(67, $progress->percentComplete);

        // Store 3 of 3 chunks
        $this->uploader->storeChunk($uploadId, 2, 'data');
        $progress = $this->uploader->getProgress($uploadId);
        self::assertSame(3, $progress->storedChunks);
        self::assertSame(100, $progress->percentComplete);
        self::assertSame([0, 1, 2], $progress->chunkIndices);
    }

    public function testCancelUploadCleansUp(): void
    {
        $result = $this->uploader->initializeUpload('test.txt', 1000, 'text/plain');
        $uploadId = $result->uploadId;

        $this->uploader->storeChunk($uploadId, 0, 'data');

        // Verify chunks exist
        self::assertCount(1, $this->storage->listChunks($uploadId));

        // Cancel upload
        $this->uploader->cancelUpload($uploadId);

        // Verify cleanup
        self::assertNull($this->storage->getMetadata($uploadId));
        self::assertEmpty($this->storage->listChunks($uploadId));
    }

    public function testCompletionIsIdempotentAndCancelDoesNotDeleteUploadedFile(): void
    {
        $pending = $this->uploader->initializeUpload('stable.txt', 4, 'text/plain');
        $this->uploader->storeChunk($pending->uploadId, 0, 'data');

        $first = $this->uploader->completeUpload($pending->uploadId);
        $second = $this->uploader->completeUpload($pending->uploadId);
        $this->uploader->cancelUpload($pending->uploadId);

        self::assertEquals($first, $second);
        self::assertTrue($this->storage->exists($first->getStoragePath()));
    }

    public function testGetProgressOnUnknownUploadReturnsZeroProgress(): void
    {
        $progress = $this->uploader->getProgress('unknown');
        self::assertSame(0, $progress->totalChunks);
        self::assertSame(0, $progress->percentComplete);
    }

    public function testGetConfigReturnsTheConfiguredLimits(): void
    {
        $uploader = new Uploader(
            $this->storage,
            $this->createStub(UploadUrlGeneratorInterface::class),
            new EventDispatcher(),
            chunkSize: 1024 * 1024,
            compressionEnabled: true,
            maxSize: 10_000_000,
            allowedTypes: ['image/png', 'image/webp'],
            integrityAlgorithm: 'sha512',
        );

        self::assertSame([
            'max_size' => 10_000_000,
            'allowed_types' => ['image/png', 'image/webp'],
            'chunk_size' => 1024 * 1024,
            'integrity_algorithm' => 'sha512',
            'compression' => true,
        ], $uploader->getConfig());
    }

    public function testGetNameReturnsTheConfiguredName(): void
    {
        $uploader = new Uploader(
            $this->storage,
            $this->createStub(UploadUrlGeneratorInterface::class),
            new EventDispatcher(),
            name: 'avatar',
        );

        self::assertSame('avatar', $uploader->getName());
    }

    public function testInitializeUploadWithMaxSizeConstraint(): void
    {
        $uploader = new Uploader(
            $this->storage,
            $this->createStub(UploadUrlGeneratorInterface::class),
            new EventDispatcher(),
            maxSize: 1000,
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('exceeds maximum allowed size');

        $uploader->initializeUpload('big.bin', 2000, 'application/octet-stream');
    }

    public function testCompleteUploadWithValidHash(): void
    {
        $content = 'test content for hashing';
        $hash = hash('sha256', $content);

        $result = $this->uploader->initializeUpload('test.txt', \strlen($content), 'text/plain', $hash);
        $uploadId = $result->uploadId;

        $this->uploader->storeChunk($uploadId, 0, $content);

        $uploadResult = $this->uploader->completeUpload($uploadId);

        self::assertInstanceOf(UploadedFile::class, $uploadResult);
        self::assertSame('test.txt', $uploadResult->getClientOriginalName());
    }

    public function testManyShuffledChunksReassembleBytePerfect(): void
    {
        // A realistic chunk count, stored out of order (delivery is not guaranteed
        // in-order), then assembled and compared byte-for-byte -- not just by length.
        $chunkSize = 256;
        $fileSize = 44 * $chunkSize + 137; // 44 full chunks + 1 partial = 45 chunks
        $original = random_bytes($fileSize);

        // Compression stays enabled on purpose: random bytes that happen to start
        // with the gzip magic (\x1f\x8b) must never be inflated, because chunks are
        // only decoded when the transport explicitly declares them compressed.
        $uploader = new Uploader(
            $this->storage,
            $this->createStub(UploadUrlGeneratorInterface::class),
            new EventDispatcher(),
            chunkSize: $chunkSize,
            compressionEnabled: true,
        );

        $result = $uploader->initializeUpload('large.bin', $fileSize, 'application/octet-stream');
        self::assertSame(45, $result->totalChunks);

        $indices = range(0, $result->totalChunks - 1);
        shuffle($indices);
        foreach ($indices as $i) {
            $uploader->storeChunk($result->uploadId, $i, substr($original, $i * $chunkSize, $chunkSize));
        }

        $completed = $uploader->completeUpload($result->uploadId);
        $assembled = $this->storage->read($completed->getStoragePath());

        self::assertSame($fileSize, \strlen($assembled), 'Assembled length must match the original.');
        self::assertSame(hash('sha256', $original), hash('sha256', $assembled), 'Assembled bytes must be identical to the original.');
    }

    public function testStoreChunkReportsGzipInitializationFailure(): void
    {
        $upload = $this->uploader->initializeUpload('compressed.bin', 4, 'application/octet-stream');
        NativeFunctions::mock('Symfony\\UX\\Upload\\inflate_init', false);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Failed to initialize gzip decompression');

        $this->uploader->storeChunk($upload->uploadId, 0, "\x1f\x8bdata", compressed: true);
    }
}
