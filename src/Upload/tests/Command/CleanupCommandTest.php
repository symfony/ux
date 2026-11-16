<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Upload\Command\CleanupCommand;
use Symfony\UX\Upload\Storage\LocalStorage;

#[CoversClass(CleanupCommand::class)]
final class CleanupCommandTest extends TestCase
{
    private string $tempDir;
    private LocalStorage $storage;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/ux_upload_cleanup_test_'.uniqid('', true);
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir);

        $this->storage = new LocalStorage($this->tempDir, $this->tempDir.'/.tmp', $this->filesystem);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->filesystem->remove($this->tempDir);
        }
    }

    /**
     * @return iterable<string, array{array<string, string>, int, bool}>
     */
    public static function ages(): iterable
    {
        yield 'default age prunes a 25 hour old session' => [[], 25, true];
        yield 'default age keeps a 1 hour old session' => [[], 1, false];
        yield 'minutes' => [['--age' => '30m'], 1, true];
        yield 'hours' => [['--age' => '1h'], 2, true];
        yield 'days prune an older session' => [['--age' => '2d'], 72, true];
        yield 'days keep a younger session' => [['--age' => '2d'], 47, false];
    }

    /**
     * @param array<string, string> $options
     */
    #[DataProvider('ages')]
    public function testPrunesSessionsOlderThanTheGivenAge(array $options, int $hoursOld, bool $pruned): void
    {
        $uploadId = $this->createUpload($hoursOld);
        $tester = $this->tester();

        $tester->execute($options);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Pruning operation completed', $tester->getDisplay());
        self::assertSame($pruned, [] === $this->storage->listChunks($uploadId));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidAges(): iterable
    {
        yield 'unknown unit' => ['invalid'];
        yield 'number without unit' => ['3600'];
        yield 'empty' => [''];
        yield 'negative' => ['-5h'];
    }

    #[DataProvider('invalidAges')]
    public function testRejectsAnInvalidAge(string $age): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid age format');

        $this->tester()->execute(['--age' => $age]);
    }

    private function tester(): CommandTester
    {
        return new CommandTester(new CleanupCommand($this->storage));
    }

    private function createUpload(int $hoursOld): string
    {
        $uploadId = 'test-upload-'.bin2hex(random_bytes(8));
        $this->storage->initiate($uploadId, [
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
            'createdAt' => time() - ($hoursOld * 3600),
            'totalChunks' => 1,
        ]);
        $this->storage->storeChunk($uploadId, 0, 'test data', hash('sha256', 'test data'));

        return $uploadId;
    }
}
