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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Upload\Command\CleanupCommand;
use Symfony\UX\Upload\Storage\InMemoryStorage;
use Symfony\UX\Upload\Storage\LocalStorage;

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

    public function testExecuteWithNoUploads()
    {
        $command = new CleanupCommand($this->storage);
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Pruning operation completed', $commandTester->getDisplay());
    }

    public function testExecuteWithOldUpload()
    {
        $uploadId = $this->createOldUpload(48); // 48 hours old

        $command = new CleanupCommand($this->storage);
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['--age' => '24h']);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Pruning operation completed', $commandTester->getDisplay());
        $this->assertEmpty($this->storage->listChunks($uploadId));
    }

    public function testExecuteWithRecentUpload()
    {
        $uploadId = $this->createOldUpload(1); // 1 hour old

        $command = new CleanupCommand($this->storage);
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['--age' => '24h']);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Pruning operation completed', $commandTester->getDisplay());
        $this->assertNotEmpty($this->storage->listChunks($uploadId));
    }

    public function testExecuteWithDaysOption()
    {
        $uploadId = $this->createOldUpload(72); // 72 hours = 3 days

        $command = new CleanupCommand($this->storage);
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['--age' => '2d']);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Pruning operation completed', $commandTester->getDisplay());
        $this->assertEmpty($this->storage->listChunks($uploadId));
    }

    public function testParseAgeWithInvalidFormat()
    {
        $command = new CleanupCommand($this->storage);
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid age format');

        $commandTester->execute(['--age' => 'invalid']);
    }

    public function testCommandHasCorrectName()
    {
        $command = new CleanupCommand($this->storage);

        $this->assertSame('ux:upload:cleanup', $command->getName());
    }

    public function testCommandHasDescription()
    {
        $command = new CleanupCommand($this->storage);

        $this->assertSame('Clean up expired completed uploads and stale upload sessions', $command->getDescription());
    }

    // --- New tests below ---

    #[Test]
    public function defaultAgeOptionIs24Hours(): void
    {
        $command = new CleanupCommand($this->storage);

        $definition = $command->getDefinition();
        $ageOption = $definition->getOption('age');

        $this->assertSame('24h', $ageOption->getDefault());
    }

    #[Test]
    public function parseAgeAcceptsMinutesFormat(): void
    {
        $command = new CleanupCommand($this->storage);
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);

        $commandTester->execute(['--age' => '30m']);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    #[Test]
    public function parseAgeRejectsNumericOnlyFormat(): void
    {
        $command = new CleanupCommand($this->storage);
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid age format');

        $commandTester->execute(['--age' => '3600']);
    }

    #[Test]
    public function parseAgeRejectsEmptyString(): void
    {
        $command = new CleanupCommand($this->storage);
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid age format');

        $commandTester->execute(['--age' => '']);
    }

    #[Test]
    public function parseAgeAcceptsOneHour(): void
    {
        $uploadId = $this->createOldUpload(2); // 2 hours old

        $command = new CleanupCommand($this->storage);
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['--age' => '1h']);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertEmpty($this->storage->listChunks($uploadId));
    }

    #[Test]
    public function parseAgeAcceptsOneDay(): void
    {
        $uploadId = $this->createOldUpload(48); // 48 hours old

        $command = new CleanupCommand($this->storage);
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['--age' => '1d']);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertEmpty($this->storage->listChunks($uploadId));
    }

    #[Test]
    public function executeReturnsSuccessCode(): void
    {
        $command = new CleanupCommand($this->storage);
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    #[Test]
    public function executeWithDefaultAgeOption(): void
    {
        // Upload is 25 hours old, default age is 24h, so it should be pruned
        $uploadId = $this->createOldUpload(25);

        $command = new CleanupCommand($this->storage);
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]); // Uses default --age=24h

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertEmpty($this->storage->listChunks($uploadId));
    }

    #[Test]
    public function commandAcceptsInMemoryStorage(): void
    {
        $inMemoryStorage = new InMemoryStorage();
        $inMemoryStorage->initiate('test-upload', [
            'filename' => 'test.txt',
            'totalChunks' => 1,
            'createdAt' => time() - 86500, // older than 24h
        ]);
        $inMemoryStorage->storeChunk('test-upload', 0, 'data', hash('sha256', 'data'));

        $command = new CleanupCommand($inMemoryStorage);
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertNull($inMemoryStorage->getMetadata('test-upload'));
    }

    #[Test]
    public function parseAgeRejectsNegativeValues(): void
    {
        $command = new CleanupCommand($this->storage);
        $application = new Application();
        $application->addCommand($command);

        $commandTester = new CommandTester($command);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid age format');

        $commandTester->execute(['--age' => '-5h']);
    }

    private function createOldUpload(int $hoursOld): string
    {
        $uploadId = 'test-upload-'.bin2hex(random_bytes(8));
        $createdAt = time() - ($hoursOld * 3600);

        $metadata = [
            'filename' => 'test.txt',
            'fileSize' => 1000,
            'mimeType' => 'text/plain',
            'createdAt' => $createdAt,
            'totalChunks' => 1,
        ];

        $this->storage->initiate($uploadId, $metadata);
        $this->storage->storeChunk($uploadId, 0, 'test data', hash('sha256', 'test data'));

        return $uploadId;
    }
}
