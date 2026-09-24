<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Storage;

use League\Flysystem\Filesystem as FlysystemFilesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Upload\Exception\InvalidArgumentException;
use Symfony\UX\Upload\Exception\StorageException;
use Symfony\UX\Upload\Exception\UploadSessionNotFoundException;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Storage\AbstractStorage;
use Symfony\UX\Upload\Storage\AssembledUpload;
use Symfony\UX\Upload\Storage\ChunkWriteResult;
use Symfony\UX\Upload\Storage\FlysystemStorage;
use Symfony\UX\Upload\Storage\InMemoryStorage;
use Symfony\UX\Upload\Storage\LocalStorage;
use Symfony\UX\Upload\Storage\PrunableStorageInterface;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Test\UploadedFileFactory;
use Symfony\UX\Upload\Upload\UploadedFile;
use Symfony\UX\Upload\Upload\UploadedFileAccess;

/**
 * Flysystem runs on a real local adapter.
 */
#[CoversClass(AbstractStorage::class)]
#[CoversClass(AssembledUpload::class)]
#[CoversClass(InMemoryStorage::class)]
#[CoversClass(LocalStorage::class)]
#[CoversClass(FlysystemStorage::class)]
final class StorageContractTest extends TestCase
{
    private const ID = '0123456789abcdef0123456789abcdef';

    /** @var list<string> */
    private array $temporaryDirectories = [];

    private string $sessionRoot = '';

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->temporaryDirectories);
    }

    /** @return iterable<string, array{string}> */
    public static function storageNames(): iterable
    {
        yield 'in-memory' => ['memory'];
        yield 'local' => ['local'];
        yield 'Flysystem' => ['flysystem'];
    }

    /** @return iterable<string, array{string}> */
    public static function filesystemStorageNames(): iterable
    {
        yield 'local' => ['local'];
        yield 'Flysystem' => ['flysystem'];
    }

    #[DataProvider('storageNames')]
    public function testWritesReadsAndDeletesFiles(string $name): void
    {
        $storage = $this->createStorage($name);
        $stream = fopen('php://memory', 'r+');
        self::assertIsResource($stream);
        fwrite($stream, 'stream content');
        rewind($stream);

        $storage->write('documents/string.txt', 'Hello World');
        $storage->write('documents/stream.txt', $stream);
        fclose($stream);

        self::assertTrue($storage->exists('documents/string.txt'));
        self::assertSame('Hello World', $this->contents($storage, 'documents/string.txt'));
        self::assertSame('stream content', $this->contents($storage, 'documents/stream.txt'));
        self::assertFalse($storage->exists('documents/unknown.txt'));

        $storage->delete('documents/string.txt');
        $storage->delete('documents/string.txt');

        self::assertFalse($storage->exists('documents/string.txt'));
        self::assertTrue($storage->exists('documents/stream.txt'));
    }

    #[DataProvider('storageNames')]
    public function testReadingAMissingFileFails(string $name): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('not found');

        $this->createStorage($name)->read('missing.txt');
    }

    #[DataProvider('storageNames')]
    public function testKeepsSessionMetadata(string $name): void
    {
        $storage = $this->createStorage($name);
        $metadata = ['filename' => 'doc.pdf', 'fileSize' => 5000, 'totalChunks' => 3, 'mimeType' => 'application/pdf'];

        self::assertNull($storage->getMetadata(self::ID));
        $storage->initiate(self::ID, $metadata);

        self::assertSame($metadata, $storage->getMetadata(self::ID));
    }

    #[DataProvider('storageNames')]
    public function testStoringAChunkRequiresASession(string $name): void
    {
        $this->expectException(UploadSessionNotFoundException::class);

        $this->createStorage($name)->storeChunk(self::ID, 0, 'data', hash('sha256', 'data'));
    }

    #[DataProvider('storageNames')]
    public function testAnIdenticalChunkRetryIsIdempotent(string $name): void
    {
        $storage = $this->createStorage($name);
        $storage->initiate(self::ID, ['filename' => 'f.txt', 'totalChunks' => 1]);

        self::assertSame(ChunkWriteResult::Stored, $storage->storeChunk(self::ID, 0, 'first', hash('sha256', 'first')));
        self::assertSame(ChunkWriteResult::AlreadyPresent, $storage->storeChunk(self::ID, 0, 'first', hash('sha256', 'first')));
        self::assertSame([0], $storage->listChunks(self::ID));
    }

    #[DataProvider('storageNames')]
    public function testADivergentChunkIsRejectedAndTheOriginalKept(string $name): void
    {
        $storage = $this->createStorage($name);
        $storage->initiate(self::ID, ['filename' => 'f.txt', 'totalChunks' => 1]);
        $storage->storeChunk(self::ID, 0, 'first', hash('sha256', 'first'));

        try {
            $storage->storeChunk(self::ID, 0, 'other', hash('sha256', 'other'));
            self::fail('A chunk with different content must be rejected.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('different content', $exception->getMessage());
        }

        self::assertSame('first', $this->contents($storage, $storage->assemble(self::ID)->path));
    }

    #[DataProvider('storageNames')]
    public function testListsStoredChunksInOrder(string $name): void
    {
        $storage = $this->createStorage($name);
        $storage->initiate(self::ID, ['filename' => 'f.txt', 'totalChunks' => 12]);
        // Indexes past 9 expose a lexicographic directory listing.
        foreach ([11, 3, 0, 10, 2, 9, 1, 8, 4, 7, 5, 6] as $index) {
            $storage->storeChunk(self::ID, $index, (string) $index, hash('sha256', (string) $index));
        }

        self::assertSame(range(0, 11), $storage->listChunks(self::ID));
        self::assertSame([], $storage->listChunks('fedcba9876543210fedcba9876543210'));
    }

    #[DataProvider('storageNames')]
    public function testAssemblesChunksInOrderAndMeasuresThem(string $name): void
    {
        $storage = $this->createStorage($name);
        $storage->initiate(self::ID, ['filename' => 'greeting.txt', 'fileSize' => 11, 'totalChunks' => 2]);
        $storage->storeChunk(self::ID, 1, 'World', hash('sha256', 'World'));
        $storage->storeChunk(self::ID, 0, 'Hello ', hash('sha256', 'Hello '));

        $assembled = $storage->assemble(self::ID, 'sha256');

        self::assertTrue($storage->exists($assembled->path));
        self::assertSame('Hello World', $this->contents($storage, $assembled->path));
        self::assertSame(11, $assembled->size);
        self::assertSame(hash('sha256', 'Hello World'), $assembled->hash);
        self::assertNull($storage->assemble(self::ID)->hash);
    }

    #[DataProvider('storageNames')]
    public function testAssemblyRejectsAMissingChunk(string $name): void
    {
        $storage = $this->createStorage($name);
        $storage->initiate(self::ID, ['filename' => 'f.txt', 'totalChunks' => 3]);
        $storage->storeChunk(self::ID, 0, 'A', hash('sha256', 'A'));
        $storage->storeChunk(self::ID, 2, 'C', hash('sha256', 'C'));

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Missing chunk: 1');

        $storage->assemble(self::ID);
    }

    #[DataProvider('storageNames')]
    public function testAssemblyRequiresASession(string $name): void
    {
        $this->expectException(UploadSessionNotFoundException::class);

        $this->createStorage($name)->assemble(self::ID);
    }

    #[DataProvider('storageNames')]
    public function testCompletedPathKeepsANormalizedExtension(string $name): void
    {
        $storage = $this->createStorage($name);
        $expiresAt = time() + 3600;
        foreach (['report.PDF' => '.pdf', 'LICENSE' => ''] as $filename => $suffix) {
            $id = md5($filename);
            $storage->initiate($id, ['filename' => $filename, 'totalChunks' => 1]);
            $storage->storeChunk($id, 0, 'data', hash('sha256', 'data'));

            self::assertSame(\sprintf('.tmp/completed/%d-%s%s', $expiresAt, $id, $suffix), $storage->assemble($id, expiresAt: $expiresAt)->path);
        }
    }

    #[DataProvider('storageNames')]
    public function testAssemblyReplacesAnUncommittedObjectAtTheCompletedPath(string $name): void
    {
        $storage = $this->createStorage($name);
        $expiresAt = time() + 3600;
        $storage->initiate(self::ID, ['filename' => 'file.txt', 'fileSize' => 4, 'totalChunks' => 1]);
        $storage->storeChunk(self::ID, 0, 'good', hash('sha256', 'good'));
        $storage->write(\sprintf('.tmp/completed/%d-%s.txt', $expiresAt, self::ID), 'partial leftover');

        $assembled = $storage->assemble(self::ID, 'sha256', $expiresAt);

        self::assertSame('good', $this->contents($storage, $assembled->path));
        self::assertSame(4, $assembled->size);
    }

    #[DataProvider('storageNames')]
    public function testCompletingASessionKeepsItsMetadataAndDropsItsChunks(string $name): void
    {
        $storage = $this->createStorage($name);
        $storage->initiate(self::ID, ['filename' => 'file.txt', 'totalChunks' => 1, 'ownerId' => 'user-1']);
        $storage->storeChunk(self::ID, 0, 'data', hash('sha256', 'data'));
        $metadata = ['filename' => 'file.txt', 'ownerId' => 'user-1', 'completedPath' => '.tmp/completed/file.txt'];

        $storage->completeSession(self::ID, $metadata);

        self::assertSame($metadata, $storage->getMetadata(self::ID));
        self::assertSame([], $storage->listChunks(self::ID));
        self::assertSame(0, $storage->countPendingByContext(new UploadContext('user-1')));
    }

    #[DataProvider('storageNames')]
    public function testAbortRemovesTheWholeSession(string $name): void
    {
        $storage = $this->createStorage($name);
        $storage->initiate(self::ID, ['filename' => 'file.txt', 'totalChunks' => 2, 'ownerId' => 'user-1']);
        $storage->initiate('fedcba9876543210fedcba9876543210', ['filename' => 'other.txt', 'totalChunks' => 1, 'ownerId' => 'user-1']);
        $storage->storeChunk(self::ID, 0, 'A', hash('sha256', 'A'));

        $storage->abort(self::ID);
        $storage->abort(self::ID);

        self::assertNull($storage->getMetadata(self::ID));
        self::assertSame([], $storage->listChunks(self::ID));
        self::assertSame(1, $storage->countPendingByContext(new UploadContext('user-1')));
        self::assertNotNull($storage->getMetadata('fedcba9876543210fedcba9876543210'));
    }

    #[DataProvider('storageNames')]
    public function testCountsPendingSessionsByContext(string $name): void
    {
        $storage = $this->createStorage($name);
        $storage->initiate('mine-1', ['filename' => 'a.txt', 'ownerId' => 'user-1']);
        $storage->initiate('mine-2', ['filename' => 'b.txt', 'ownerId' => 'user-1']);
        $storage->initiate('other-owner', ['filename' => 'c.txt', 'ownerId' => 'user-2']);
        $storage->initiate('other-tenant', ['filename' => 'd.txt', 'ownerId' => 'user-1', 'tenantId' => 'acme']);
        $storage->initiate('done', ['filename' => 'e.txt', 'ownerId' => 'user-1']);
        $storage->completeSession('done', ['filename' => 'e.txt', 'ownerId' => 'user-1', 'completedPath' => '.tmp/completed/e.txt']);

        self::assertSame(2, $storage->countPendingByContext(new UploadContext('user-1')));
        self::assertSame(1, $storage->countPendingByContext(new UploadContext('user-1', 'acme')));
        self::assertSame(1, $storage->countPendingByContext(new UploadContext('user-2')));
        self::assertSame(0, $storage->countPendingByContext(new UploadContext('nobody')));
    }

    #[DataProvider('storageNames')]
    public function testPruneRemovesStaleSessionsAndKeepsFreshOnes(string $name): void
    {
        $storage = $this->prunableStorage($name);
        $storage->initiate('stale', ['filename' => 'old.txt', 'totalChunks' => 1, 'createdAt' => time() - 7200]);
        $storage->storeChunk('stale', 0, 'old', hash('sha256', 'old'));
        $storage->initiate('undated', ['filename' => 'undated.txt', 'totalChunks' => 1]);
        $storage->initiate('fresh', ['filename' => 'new.txt', 'totalChunks' => 1, 'createdAt' => time() - 300]);
        $storage->storeChunk('fresh', 0, 'new', hash('sha256', 'new'));

        $storage->prune(3600);

        self::assertNull($storage->getMetadata('stale'));
        self::assertSame([], $storage->listChunks('stale'));
        self::assertNull($storage->getMetadata('undated'));
        self::assertNotNull($storage->getMetadata('fresh'));
        self::assertSame([0], $storage->listChunks('fresh'));
        self::assertSame(1, $storage->countPendingByContext(new UploadContext()));
    }

    #[DataProvider('storageNames')]
    public function testPruneRemovesAnExpiredCompletedSessionAndItsObject(string $name): void
    {
        $storage = $this->prunableStorage($name);
        $path = '.tmp/completed/'.(time() + 3600).'-'.self::ID.'.txt';
        $storage->write($path, 'data');
        $storage->initiate('expired', ['filename' => 'file.txt', 'createdAt' => time() - 60]);
        $storage->completeSession('expired', ['filename' => 'file.txt', 'completedPath' => $path, 'expiresAt' => time() - 1]);

        $storage->prune(3600);

        self::assertFalse($storage->exists($path));
        self::assertNull($storage->getMetadata('expired'));
    }

    #[DataProvider('storageNames')]
    public function testPruneDeletesOnlyExpiredGeneratedCompletedFiles(string $name): void
    {
        $storage = $this->prunableStorage($name);
        $expired = '.tmp/completed/'.(time() - 1).'-'.self::ID.'.txt';
        $fresh = '.tmp/completed/'.(time() + 3600).'-fedcba9876543210fedcba9876543210.txt';
        $applicationFile = 'documents/keep.txt';
        $unrecognized = '.tmp/completed/not-a-generated-key.txt';
        foreach ([$expired, $fresh, $applicationFile, $unrecognized] as $path) {
            $storage->write($path, 'data');
        }

        $storage->prune(3600);

        self::assertFalse($storage->exists($expired));
        self::assertTrue($storage->exists($fresh));
        self::assertTrue($storage->exists($applicationFile));
        self::assertTrue($storage->exists($unrecognized));
    }

    #[DataProvider('storageNames')]
    public function testPruningAnEmptyStorageIsANoOp(string $name): void
    {
        $storage = $this->prunableStorage($name);

        $storage->prune(3600);

        self::assertSame(0, $storage->countPendingByContext(new UploadContext()));
    }

    #[DataProvider('storageNames')]
    public function testReportsWhetherItIsSharedBetweenHosts(string $name): void
    {
        self::assertSame('flysystem' === $name, $this->createStorage($name)->isDistributed());
    }

    #[DataProvider('filesystemStorageNames')]
    public function testRejectsAnUploadIdThatIsNotASinglePathSegment(string $name): void
    {
        $storage = $this->createStorage($name);

        foreach (['', '.', '..', 'upload.123', 'foo/bar', '../etc/passwd'] as $uploadId) {
            try {
                $storage->initiate($uploadId, ['filename' => 'test.txt', 'totalChunks' => 1]);
                self::fail(\sprintf('Upload ID "%s" must be rejected.', $uploadId));
            } catch (StorageException $exception) {
                self::assertStringContainsString('Invalid upload ID', $exception->getMessage());
            }
        }

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Invalid upload ID');
        $storage->listChunks('..');
    }

    #[DataProvider('filesystemStorageNames')]
    public function testPruneRemovesSessionsWithMissingOrUnreadableMetadata(string $name): void
    {
        $storage = $this->prunableStorage($name);
        $this->writeSessionFile('orphan/chunk_0', 'data');
        $this->writeSessionFile('corrupt/metadata.json', '{invalid json');
        $this->writeSessionFile('stray-file', 'not a session');

        $storage->prune(3600);

        self::assertDirectoryDoesNotExist($this->sessionRoot.'/orphan');
        self::assertDirectoryDoesNotExist($this->sessionRoot.'/corrupt');
        self::assertFileExists($this->sessionRoot.'/stray-file');
    }

    #[DataProvider('filesystemStorageNames')]
    public function testRejectsMetadataThatIsNotAMap(string $name): void
    {
        $storage = $this->createStorage($name);

        foreach (['true', '["value"]'] as $index => $json) {
            $this->writeSessionFile('invalid-'.$index.'/metadata.json', $json);
            try {
                $storage->getMetadata('invalid-'.$index);
                self::fail('Invalid metadata must be rejected.');
            } catch (StorageException $exception) {
                self::assertStringContainsString('Invalid upload metadata', $exception->getMessage());
            }
        }
    }

    #[DataProvider('filesystemStorageNames')]
    public function testPruneDropsPendingMarkersLeftWithoutASession(string $name): void
    {
        $storage = $this->prunableStorage($name);
        $context = new UploadContext('ghost');
        $marker = '.pending/'.$context->fingerprint().'/vanished';
        $this->writeSessionFile($marker, '');
        $this->writeSessionFile('corrupt-count/metadata.json', '{invalid');

        self::assertSame(1, $storage->countPendingByContext($context));
        self::assertSame(0, $storage->countPendingByContext(new UploadContext()));

        $storage->prune(3600);

        self::assertSame(0, $storage->countPendingByContext($context));
        self::assertFileDoesNotExist($this->sessionRoot.'/'.$marker);
    }

    #[DataProvider('storageNames')]
    public function testUploadedFileReadsIdenticalBytes(string $name): void
    {
        $storage = $this->createStorage($name);
        $content = str_repeat('0123456789abcdef', 131_072);
        $upload = new UploadedFileFactory(size: \strlen($content))->create($storage, $content);

        $stream = $upload->openStream();
        $hash = hash_init('sha256');
        hash_update_stream($hash, $stream);
        fclose($stream);

        self::assertSame(hash('sha256', $content), hash_final($hash));
    }

    #[DataProvider('storageNames')]
    public function testUploadedFileDeletionIsIdempotent(string $name): void
    {
        $storage = $this->createStorage($name);
        $upload = new UploadedFileFactory()->create($storage);

        $upload->delete();
        $upload->delete();

        self::assertFalse($storage->exists($upload->getStoragePath()));
    }

    #[DataProvider('storageNames')]
    public function testCleanupWinningTheDeleteRaceRemainsSafe(string $name): void
    {
        $storage = $this->prunableStorage($name);
        $expiresAt = new \DateTimeImmutable('-1 hour');
        $path = \sprintf('.tmp/completed/%d-%s.txt', $expiresAt->getTimestamp(), self::ID);
        $storage->write($path, 'data');
        $upload = new UploadedFile(
            self::ID,
            'default',
            $path,
            'document.txt',
            'text/plain',
            4,
            $expiresAt->modify('-1 hour'),
            $expiresAt,
            access: new UploadedFileAccess($storage),
        );

        $storage->prune(0);
        $upload->delete();

        self::assertFalse($storage->exists($path));
    }

    private function createStorage(string $name): StorageInterface
    {
        if ('memory' === $name) {
            return new InMemoryStorage();
        }

        $root = sys_get_temp_dir().'/ux_upload_contract_'.bin2hex(random_bytes(6));
        $this->temporaryDirectories[] = $root;

        if ('local' === $name) {
            $this->sessionRoot = $root.'/storage/.tmp';

            return new LocalStorage($root.'/storage', $this->sessionRoot);
        }

        $this->sessionRoot = $root.'/flysystem/.tmp';

        return new FlysystemStorage(
            new FlysystemFilesystem(new LocalFilesystemAdapter($root.'/flysystem')),
            $root.'/assembly',
        );
    }

    private function prunableStorage(string $name): StorageInterface&PrunableStorageInterface
    {
        $storage = $this->createStorage($name);
        self::assertInstanceOf(PrunableStorageInterface::class, $storage);

        return $storage;
    }

    private function writeSessionFile(string $relativePath, string $content): void
    {
        new Filesystem()->dumpFile($this->sessionRoot.'/'.$relativePath, $content);
    }

    private function contents(StorageInterface $storage, string $path): string
    {
        $content = $storage->read($path);
        if (!\is_resource($content)) {
            return (string) $content;
        }

        try {
            return (string) stream_get_contents($content);
        } finally {
            fclose($content);
        }
    }
}
