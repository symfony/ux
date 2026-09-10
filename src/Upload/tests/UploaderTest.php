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
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\UX\Upload\Event\UploadAssembledEvent;
use Symfony\UX\Upload\Exception\InvalidArgumentException;
use Symfony\UX\Upload\Exception\ValidationException;
use Symfony\UX\Upload\Policy\UploadPolicy;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Storage\AssembledUpload;
use Symfony\UX\Upload\Storage\FlysystemStorage;
use Symfony\UX\Upload\Storage\LocalStorage;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Tests\Mock\MockStorage;
use Symfony\UX\Upload\Upload\CompletedUpload;
use Symfony\UX\Upload\Upload\PendingUpload;
use Symfony\UX\Upload\Upload\UploadProgress;
use Symfony\UX\Upload\Uploader;
use Symfony\UX\Upload\Url\UploadUrlGeneratorInterface;

final class UploaderTest extends TestCase
{
    private Uploader $uploader;
    private MockStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new MockStorage();
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

    public function testInitializeUpload()
    {
        $result = $this->uploader->initializeUpload('test.txt', 1000, 'text/plain');

        $this->assertInstanceOf(PendingUpload::class, $result);
        $this->assertNotEmpty($result->uploadId);
        $this->assertSame('test.txt', $result->filename);
        $this->assertSame(1000, $result->fileSize);
        $this->assertSame('text/plain', $result->mimeType);
        $this->assertSame(1, $result->totalChunks);
        $this->assertSame(5 * 1024 * 1024, $result->chunkSize);
        $this->assertTrue($result->compression);
        $this->assertSame(3, $result->parallel);
    }

    public function testDirectUploadCompletesWithExactBytesAndMetadata()
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

        self::assertSame('document.txt', $completed->originalName);
        self::assertSame('text/plain', $completed->mimeType);
        self::assertSame(\strlen($content), $completed->size);
        self::assertSame(hash('sha256', $content), $completed->checksum);
        self::assertSame('owner-1', $completed->getOwnerId());
        self::assertSame('tenant-1', $completed->getTenantId());
        self::assertSame('profile.document', $completed->getFieldName());
        self::assertSame($content, $this->storage->read($completed->getTemporaryPath()));
    }

    public function testDirectUploadDecompressesBeforeDigestAndCompletion()
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

        self::assertSame($content, $this->storage->read($completed->getTemporaryPath()));
    }

    public function testDirectUploadRejectsOversizeBeforeCreatingSession()
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

    public function testDirectUploadCleansNonResumableSessionAfterFailure()
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

        self::assertSame([], $this->storage->listChunks('unused'));
        $reflection = new \ReflectionProperty($this->storage, 'metadata');
        self::assertSame([], $reflection->getValue($this->storage));
    }

    public function testDirectUploadPreservesOriginalFailureWhenCleanupAlsoFails()
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

    public function testDirectUploadProducesExactBytesWithLocalAndFlysystemStorage()
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

    public function testRejectsExcessiveChunkSizeAtRuntime()
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

    public function testRejectsInvalidConstructorConfiguration()
    {
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

    public function testAcceptsAnExplicitLockFactory()
    {
        $uploader = new Uploader(
            $this->storage,
            $this->createStub(UploadUrlGeneratorInterface::class),
            new EventDispatcher(),
            lockFactory: new LockFactory(new InMemoryStore()),
        );

        self::assertSame('default', $uploader->getName());
    }

    public function testInitializeRejectsMismatchedHashAlgorithm()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('does not match configured integrity algorithm');

        $this->uploader->initializeUpload('test.txt', 4, 'text/plain', hash('sha512', 'data'), 'sha512');
    }

    public function testDistributedStorageRequiresSharedLockGuarantee()
    {
        $storage = new FlysystemStorage($this->createStub(FilesystemOperator::class));
        $uploader = new Uploader($storage, $this->createStub(UploadUrlGeneratorInterface::class), new EventDispatcher());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('requires a configured shared lock');

        $uploader->initializeUpload('test.txt', 4, 'text/plain');
    }

    public function testLocalStorageEnforcesQuotaAndCompletesThroughVerifiedAssembly()
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

            self::assertSame(4, $completed->size);
            self::assertTrue($storage->exists($completed->getTemporaryPath()));
        } finally {
            $filesystem->remove($root);
        }
    }

    public function testStoreChunkRejectsPayloadLargerThanConfiguredChunkSize()
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

    public function testCompleteRejectsUnknownUpload()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $this->uploader->completeUpload('missing');
    }

    public function testCompleteRejectsInvalidHashAlgorithmMetadata()
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

    public function testCompleteRejectsIncompleteCompletedMetadata()
    {
        $this->storage->initiate('id', ['completedPath' => 'completed/id']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Completed upload metadata "filename" is missing');

        $this->uploader->completeUpload('id');
    }

    public function testUploadIdIsUniqueHexAndPathSafe()
    {
        $seen = [];
        for ($i = 0; $i < 500; ++$i) {
            $uploadId = $this->uploader->initializeUpload('test.txt', 1000, 'text/plain')->uploadId;

            // 32-char lowercase hex from random_bytes(16), satisfying the storage
            // path-safety regex (LocalStorage/FlysystemStorage: /^[\w\-]+$/).
            self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $uploadId);
            self::assertMatchesRegularExpression('/^[\w\-]+$/', $uploadId);
            self::assertArrayNotHasKey($uploadId, $seen);
            $seen[$uploadId] = true;
        }
    }

    public function testStoreChunk()
    {
        $result = $this->uploader->initializeUpload('test.txt', 1000, 'text/plain');
        $uploadId = $result->uploadId;
        $chunkData = 'Hello World';

        $this->uploader->storeChunk($uploadId, 0, $chunkData);

        $this->assertCount(1, $this->storage->listChunks($uploadId));
    }

    public function testStoreChunkVerifiesOptionalSha256Digest()
    {
        $upload = $this->uploader->initializeUpload('test.txt', 4, 'text/plain');

        $this->uploader->storeChunk($upload->uploadId, 0, 'data', hash('sha256', 'data'));
        self::assertSame([0], $this->storage->listChunks($upload->uploadId));

        $other = $this->uploader->initializeUpload('other.txt', 4, 'text/plain');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Integrity check failed for chunk 0');
        $this->uploader->storeChunk($other->uploadId, 0, 'data', hash('sha256', 'tampered'));
    }

    public function testStoreChunkAcceptsIdenticalRetransmissionWithoutDuplicating()
    {
        $result = $this->uploader->initializeUpload('test.txt', 1000, 'text/plain');
        $uploadId = $result->uploadId;

        $this->uploader->storeChunk($uploadId, 0, 'data');
        $this->uploader->storeChunk($uploadId, 0, 'data');

        $this->assertSame([0], $this->storage->listChunks($uploadId));
    }

    public function testStoreChunkOverwriteAttemptPreservesOriginalData()
    {
        $result = $this->uploader->initializeUpload('test.txt', 8, 'text/plain');
        $uploadId = $result->uploadId;

        $this->uploader->storeChunk($uploadId, 0, 'original');

        try {
            $this->uploader->storeChunk($uploadId, 0, 'tampered');
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('already been uploaded', $e->getMessage());
        }

        // The rejected overwrite must not add a duplicate or replace the stored chunk.
        $this->assertSame([0], $this->storage->listChunks($uploadId));

        $path = $this->uploader->completeUpload($uploadId)->getTemporaryPath();
        $this->assertSame('original', $this->storage->read($path));
    }

    public function testCompleteUploadCleansUpOnFailure()
    {
        // Force hash failure by initializing with a hash and then uploading different content
        $result = $this->uploader->initializeUpload('test.txt', 4, 'text/plain', 'invalid-hash');
        $uploadId = $result->uploadId;
        $this->uploader->storeChunk($uploadId, 0, 'data');

        try {
            $this->uploader->completeUpload($uploadId);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('integrity check failed', $e->getMessage());
        }

        // Verify cleanup: metadata should be gone because abort was called in the catch block
        $this->assertNull($this->storage->getMetadata($uploadId));
    }

    public function testCompleteUpload()
    {
        $result = $this->uploader->initializeUpload('test.txt', 4, 'text/plain');
        $uploadId = $result->uploadId;

        $this->uploader->storeChunk($uploadId, 0, 'data');

        $uploadResult = $this->uploader->completeUpload($uploadId);

        $this->assertInstanceOf(CompletedUpload::class, $uploadResult);
        $this->assertSame('test.txt', $uploadResult->originalName);
        $this->assertStringStartsWith('.tmp/completed/', $uploadResult->getTemporaryPath());
        $this->assertSame('text/plain', $uploadResult->mimeType);
        $this->assertSame(4, $uploadResult->size);
        $this->assertSame($uploadId, $uploadResult->id);
    }

    public function testCompletedTtlStartsWhenAssemblyCompletes()
    {
        $pending = $this->uploader->initializeUpload('test.txt', 4, 'text/plain');
        self::assertArrayNotHasKey('expiresAt', $this->storage->getMetadata($pending->uploadId));
        $this->uploader->storeChunk($pending->uploadId, 0, 'data');
        $completedAt = time();

        $completed = $this->uploader->completeUpload($pending->uploadId);

        self::assertGreaterThanOrEqual($completedAt + Uploader::DEFAULT_COMPLETED_TTL, $completed->expiresAt->getTimestamp());
        self::assertLessThanOrEqual(time() + Uploader::DEFAULT_COMPLETED_TTL, $completed->expiresAt->getTimestamp());
    }

    public function testInitializeUploadRejectsZeroSizeFile()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File size must be greater than zero');

        $this->uploader->initializeUpload('empty.txt', 0, 'text/plain');
    }

    public function testInitializeUploadRejectsNegativeSizeFile()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File size must be greater than zero');

        $this->uploader->initializeUpload('negative.txt', -100, 'text/plain');
    }

    public function testStoreChunkRejectsNegativeIndex()
    {
        $result = $this->uploader->initializeUpload('test.txt', 1000, 'text/plain');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Chunk index -1 is out of range');

        $this->uploader->storeChunk($result->uploadId, -1, 'data');
    }

    public function testStoreChunkRejectsIndexBeyondTotal()
    {
        // 1000 bytes with 5MB chunk size = 1 total chunk (indices 0..0)
        $result = $this->uploader->initializeUpload('test.txt', 1000, 'text/plain');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Chunk index 5 is out of range');

        $this->uploader->storeChunk($result->uploadId, 5, 'data');
    }

    public function testStoreChunkRejectsIndexEqualToTotal()
    {
        // 15MB file with 5MB chunk size = 3 total chunks (indices 0, 1, 2)
        $result = $this->uploader->initializeUpload('test.txt', 15 * 1024 * 1024, 'text/plain');
        $this->assertSame(3, $result->totalChunks);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Chunk index 3 is out of range [0, 3)');

        $this->uploader->storeChunk($result->uploadId, 3, 'data');
    }

    public function testStoreChunkAcceptsValidIndex()
    {
        // 15MB file with 5MB chunk size = 3 total chunks
        $result = $this->uploader->initializeUpload('test.txt', 15 * 1024 * 1024, 'text/plain');
        $this->assertSame(3, $result->totalChunks);

        // Index 0 of 3 should be valid
        $this->uploader->storeChunk($result->uploadId, 0, 'data');
        $this->assertCount(1, $this->storage->listChunks($result->uploadId));

        // Index 2 of 3 should be valid (last chunk)
        $this->uploader->storeChunk($result->uploadId, 2, 'data');
        $this->assertCount(2, $this->storage->listChunks($result->uploadId));
    }

    public function testStoreChunkHandlesNonCompressedData()
    {
        $result = $this->uploader->initializeUpload('test.txt', 1000, 'text/plain');
        $plainData = 'This is not gzip compressed data';

        // Should not throw, should store data as-is
        $this->uploader->storeChunk($result->uploadId, 0, $plainData);

        $this->assertCount(1, $this->storage->listChunks($result->uploadId));
    }

    public function testStoreChunkHandlesGzipCompressedData()
    {
        $result = $this->uploader->initializeUpload('test.txt', 1000, 'text/plain');
        $originalData = 'This is the original content';
        $compressedData = gzencode($originalData);

        $this->uploader->storeChunk($result->uploadId, 0, $compressedData, compressed: true);

        $this->assertCount(1, $this->storage->listChunks($result->uploadId));
    }

    public function testStoreChunkStoresUndeclaredGzipLookalikeVerbatim()
    {
        // A binary chunk may legitimately start with the gzip magic bytes.
        // Without the explicit compressed flag it must be stored as-is, never
        // sniffed and inflated.
        $result = $this->uploader->initializeUpload('test.bin', 1000, 'application/octet-stream');
        $lookalike = "\x1f\x8b\x00\x00not-actually-gzip";

        $this->uploader->storeChunk($result->uploadId, 0, $lookalike);

        $this->assertCount(1, $this->storage->listChunks($result->uploadId));
    }

    public function testStoreChunkRejectsDeclaredCompressionWhenDisabled()
    {
        $uploader = new Uploader(
            $this->storage,
            $this->createStub(UploadUrlGeneratorInterface::class),
            new EventDispatcher(),
        );
        $result = $uploader->initializeUpload('test.txt', 1000, 'text/plain');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('compression is not enabled');

        $uploader->storeChunk($result->uploadId, 0, gzencode('data'), compressed: true);
    }

    public function testStoreChunkThrowsOnCorruptGzipData()
    {
        $result = $this->uploader->initializeUpload('test.txt', 1000, 'text/plain');

        // Declared compressed but the payload is not valid gzip
        $corruptGzipData = "\x1f\x8b\x00\x00\x00\x00\x00\x00corrupt-not-valid-gzip";

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Failed to decompress gzip chunk 0');

        $this->uploader->storeChunk($result->uploadId, 0, $corruptGzipData, compressed: true);
    }

    public function testStoreChunkRejectsDecompressedDataLargerThanChunkSize()
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

        $uploader->storeChunk($result->uploadId, 0, gzencode('123456789'), compressed: true);
    }

    public function testCompleteUploadRejectsAssembledSizeMismatch()
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

    public function testGetProgressReturnsCorrectPercentage()
    {
        $result = $this->uploader->initializeUpload('test.txt', 15 * 1024 * 1024, 'text/plain');
        $uploadId = $result->uploadId;

        // With 5MB chunk size, 15MB file should have 3 chunks
        $this->assertSame(3, $result->totalChunks);

        // Store 1 of 3 chunks
        $this->uploader->storeChunk($uploadId, 0, 'data');
        $progress = $this->uploader->getProgress($uploadId);
        $this->assertInstanceOf(UploadProgress::class, $progress);
        $this->assertSame($uploadId, $progress->uploadId);
        $this->assertSame(1, $progress->storedChunks);
        $this->assertSame(3, $progress->totalChunks);
        $this->assertSame(33, $progress->percentComplete);

        // Store 2 of 3 chunks
        $this->uploader->storeChunk($uploadId, 1, 'data');
        $progress = $this->uploader->getProgress($uploadId);
        $this->assertSame(2, $progress->storedChunks);
        $this->assertSame(67, $progress->percentComplete);

        // Store 3 of 3 chunks
        $this->uploader->storeChunk($uploadId, 2, 'data');
        $progress = $this->uploader->getProgress($uploadId);
        $this->assertSame(3, $progress->storedChunks);
        $this->assertSame(100, $progress->percentComplete);
        $this->assertSame([0, 1, 2], $progress->chunkIndices);
    }

    public function testCancelUploadCleansUp()
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

    public function testCompletionIsIdempotentAndCancelDoesNotDeleteCompletedUpload()
    {
        $pending = $this->uploader->initializeUpload('stable.txt', 4, 'text/plain');
        $this->uploader->storeChunk($pending->uploadId, 0, 'data');

        $first = $this->uploader->completeUpload($pending->uploadId);
        $second = $this->uploader->completeUpload($pending->uploadId);
        $this->uploader->cancelUpload($pending->uploadId);

        self::assertEquals($first, $second);
        self::assertTrue($this->storage->exists($first->getTemporaryPath()));
    }

    public function testGetProgressOnUnknownUploadReturnsZeroProgress()
    {
        $progress = $this->uploader->getProgress('unknown');
        self::assertSame(0, $progress->totalChunks);
        self::assertSame(0, $progress->percentComplete);
    }

    public function testGetPercentCompleteWithZeroTotalChunks()
    {
        $reflection = new \ReflectionClass(Uploader::class);
        $method = $reflection->getMethod('getPercentComplete');

        $result = $method->invoke($this->uploader, 0, 0);
        $this->assertSame(0, $result);
    }

    public function testGetNameReturnsDefault()
    {
        self::assertSame('default', $this->uploader->getName());
    }

    public function testGetNameReturnsCustomName()
    {
        $uploader = new Uploader(
            $this->storage,
            $this->createStub(UploadUrlGeneratorInterface::class),
            new EventDispatcher(),
            name: 'avatar',
        );

        self::assertSame('avatar', $uploader->getName());
    }

    public function testGetConfig()
    {
        $config = $this->uploader->getConfig();

        self::assertArrayHasKey('max_size', $config);
        self::assertArrayHasKey('allowed_types', $config);
        self::assertArrayHasKey('chunk_size', $config);
        self::assertSame(Uploader::DEFAULT_MAX_SIZE, $config['max_size']);
        self::assertSame([], $config['allowed_types']);
        self::assertSame(5 * 1024 * 1024, $config['chunk_size']);
    }

    public function testGetConfigWithCustomValues()
    {
        $uploader = new Uploader(
            $this->storage,
            $this->createStub(UploadUrlGeneratorInterface::class),
            new EventDispatcher(),
            maxSize: 10_000_000,
            allowedTypes: ['image/jpeg', 'image/png'],
            chunkSize: 1024 * 1024,
        );

        $config = $uploader->getConfig();

        self::assertSame(10_000_000, $config['max_size']);
        self::assertSame(['image/jpeg', 'image/png'], $config['allowed_types']);
        self::assertSame(1024 * 1024, $config['chunk_size']);
    }

    public function testInitializeUploadWithMaxSizeConstraint()
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

    public function testCompleteUploadWithValidHash()
    {
        $content = 'test content for hashing';
        $hash = hash('sha256', $content);

        $result = $this->uploader->initializeUpload('test.txt', \strlen($content), 'text/plain', $hash);
        $uploadId = $result->uploadId;

        $this->uploader->storeChunk($uploadId, 0, $content);

        $uploadResult = $this->uploader->completeUpload($uploadId);

        self::assertInstanceOf(CompletedUpload::class, $uploadResult);
        self::assertSame('test.txt', $uploadResult->originalName);
    }

    public function testCompleteUploadWithInvalidHashThrows()
    {
        $content = 'test content';
        $wrongHash = hash('sha256', 'different content');

        $result = $this->uploader->initializeUpload('test.txt', \strlen($content), 'text/plain', $wrongHash);
        $uploadId = $result->uploadId;

        $this->uploader->storeChunk($uploadId, 0, $content);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('File integrity check failed');

        $this->uploader->completeUpload($uploadId);
    }

    public function testInitializeUploadWithHash()
    {
        $hash = 'abc123def456';

        $result = $this->uploader->initializeUpload('test.txt', 100, 'text/plain', $hash);

        self::assertNotEmpty($result->uploadId);

        $metadata = $this->storage->getMetadata($result->uploadId);
        self::assertSame($hash, $metadata['hash']);
    }

    public function testInitializeUploadWithNullHash()
    {
        $result = $this->uploader->initializeUpload('test.txt', 100, 'text/plain', null);

        $metadata = $this->storage->getMetadata($result->uploadId);
        self::assertNull($metadata['hash']);
    }

    public function testMultiChunkCompleteUpload()
    {
        $chunkSize = 100;
        $totalSize = 300;
        $uploader = new Uploader(
            $this->storage,
            $this->createStub(UploadUrlGeneratorInterface::class),
            new EventDispatcher(),
            chunkSize: $chunkSize,
        );

        $result = $uploader->initializeUpload('large.bin', $totalSize, 'application/octet-stream');
        self::assertSame(3, $result->totalChunks);

        $uploader->storeChunk($result->uploadId, 0, str_repeat('A', $chunkSize));
        $uploader->storeChunk($result->uploadId, 1, str_repeat('B', $chunkSize));
        $uploader->storeChunk($result->uploadId, 2, str_repeat('C', $chunkSize));

        $uploadResult = $uploader->completeUpload($result->uploadId);

        self::assertInstanceOf(CompletedUpload::class, $uploadResult);
        self::assertSame($totalSize, $uploadResult->size);
    }

    public function testManyShuffledChunksReassembleBytePerfect()
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
        $assembled = $this->storage->read($completed->getTemporaryPath());

        self::assertIsString($assembled);
        self::assertSame($fileSize, \strlen($assembled), 'Assembled length must match the original.');
        self::assertSame(hash('sha256', $original), hash('sha256', $assembled), 'Assembled bytes must be identical to the original.');
    }

    public function testCompleteUploadAcceptsMatchingAssembledHash()
    {
        $content = 'test content';
        $hash = hash('sha256', $content);

        $storage = $this->createStub(StorageInterface::class);
        $storage->method('getMetadata')->willReturn(['filename' => 'f', 'fileSize' => \strlen($content), 'mimeType' => 'm', 'totalChunks' => 1, 'hash' => $hash]);
        $storage->method('assemble')->willReturn(new AssembledUpload('p', \strlen($content), $hash));

        $uploader = new Uploader($storage, $this->createStub(UploadUrlGeneratorInterface::class), new EventDispatcher());
        $result = $uploader->completeUpload('id');

        $this->assertSame('f', $result->originalName);
    }

    public function testCompleteUploadRejectsMismatchingAssembledHash()
    {
        $content = 'test content';

        $storage = $this->createStub(StorageInterface::class);
        $storage->method('getMetadata')->willReturn(['filename' => 'f', 'fileSize' => \strlen($content), 'mimeType' => 'm', 'totalChunks' => 1, 'hash' => hash('sha256', $content)]);
        $storage->method('assemble')->willReturn(new AssembledUpload('p', \strlen($content), hash('sha256', 'tampered')));

        $uploader = new Uploader($storage, $this->createStub(UploadUrlGeneratorInterface::class), new EventDispatcher());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('File integrity check failed');

        $uploader->completeUpload('id');
    }

    public function testStoreChunkReportsGzipInitializationFailure()
    {
        $upload = $this->uploader->initializeUpload('compressed.bin', 4, 'application/octet-stream');
        NativeFunctions::mock('Symfony\\UX\\Upload\\inflate_init', false);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Failed to initialize gzip decompression');

        $this->uploader->storeChunk($upload->uploadId, 0, "\x1f\x8bdata", compressed: true);
    }

    public function testStoreChunkWithCompressionDisabled()
    {
        $urlGenerator = $this->createStub(UploadUrlGeneratorInterface::class);
        $urlGenerator->method('generateUploadUrl')->willReturn('http://example.com/upload/123');

        $uploader = new Uploader(
            $this->storage,
            $urlGenerator,
            new EventDispatcher(),
            compressionEnabled: false,
        );

        $result = $uploader->initializeUpload('test.txt', 1000, 'text/plain');

        // Store gzip-looking data; with compression disabled, should store as-is
        $data = "\x1f\x8b".str_repeat('x', 10);
        $uploader->storeChunk($result->uploadId, 0, $data);

        $this->assertCount(1, $this->storage->listChunks($result->uploadId));
    }
}
