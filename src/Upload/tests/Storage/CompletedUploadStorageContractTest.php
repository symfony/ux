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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Upload\Storage\FlysystemStorage;
use Symfony\UX\Upload\Storage\InMemoryStorage;
use Symfony\UX\Upload\Storage\LocalStorage;
use Symfony\UX\Upload\Storage\PrunableStorageInterface;
use Symfony\UX\Upload\Storage\StorageInterface;
use Symfony\UX\Upload\Test\CompletedUploadFactory;
use Symfony\UX\Upload\Upload\CompletedUpload;
use Symfony\UX\Upload\Upload\CompletedUploadAccess;

final class CompletedUploadStorageContractTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryDirectories = [];

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

    #[DataProvider('storageNames')]
    public function testCompletedUploadReadsIdenticalBytes(string $storageName): void
    {
        $storage = $this->createStorage($storageName);
        $content = str_repeat('0123456789abcdef', 131_072);
        $upload = new CompletedUploadFactory(size: \strlen($content))->create($storage, $content);

        $stream = $upload->openStream();
        $hash = hash_init('sha256');
        hash_update_stream($hash, $stream);
        fclose($stream);

        self::assertSame(hash('sha256', $content), hash_final($hash));
    }

    #[DataProvider('storageNames')]
    public function testCompletedUploadDeletionIsIdempotent(string $storageName): void
    {
        $storage = $this->createStorage($storageName);
        $upload = new CompletedUploadFactory()->create($storage);

        $upload->delete();
        $upload->delete();

        self::assertFalse($storage->exists($upload->getTemporaryPath()));
    }

    #[DataProvider('storageNames')]
    public function testCleanupWinningTheDeleteRaceRemainsSafe(string $storageName): void
    {
        $storage = $this->createStorage($storageName);
        self::assertInstanceOf(PrunableStorageInterface::class, $storage);

        $id = '0123456789abcdef0123456789abcdef';
        $expiresAt = new \DateTimeImmutable('-1 hour');
        $path = \sprintf('.tmp/completed/%d-%s.txt', $expiresAt->getTimestamp(), $id);
        $storage->write($path, 'data');
        $upload = new CompletedUpload(
            $id,
            'default',
            $path,
            'document.txt',
            'text/plain',
            4,
            $expiresAt->modify('-1 hour'),
            $expiresAt,
            access: new CompletedUploadAccess($storage),
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
            return new LocalStorage($root.'/storage', $root.'/tmp');
        }

        return new FlysystemStorage(
            new FlysystemFilesystem(new LocalFilesystemAdapter($root.'/flysystem')),
            $root.'/assembly',
        );
    }
}
