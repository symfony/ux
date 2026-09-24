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

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Uses real OS processes: an in-process test cannot contend for the lifecycle lock.
 */
#[Group('concurrency')]
#[CoversNothing]
final class ConcurrentChunkWriteTest extends TestCase
{
    private const WORKER_COUNT = 6;
    private const PAYLOAD_LENGTH = 128 * 1024;

    private Filesystem $filesystem;
    private string $root;
    private string $tempDir;
    private string $uploadId;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->root = sys_get_temp_dir().'/ux_upload_concurrency_'.bin2hex(random_bytes(6));
        $this->filesystem->mkdir($this->root);
        $this->tempDir = $this->root.'/tmp';
        $this->uploadId = bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->root);
    }

    public function testConcurrentWritesToSameChunkYieldExactlyOneWinner(): void
    {
        $this->createSession(totalChunks: 1);

        $results = $this->runWorkers(array_fill(0, self::WORKER_COUNT, 0));

        $written = array_keys($results, 'WRITTEN', true);
        $rejected = array_keys($results, 'REJECTED', true);

        self::assertCount(1, $written, \sprintf('Exactly one write must win, got: %s', implode(', ', $results)));
        self::assertCount(self::WORKER_COUNT - 1, $rejected, 'Every loser must be rejected as a duplicate.');

        $stored = (string) file_get_contents($this->tempDir.'/'.$this->uploadId.'/chunk_0');
        self::assertSame(self::PAYLOAD_LENGTH, \strlen($stored), 'Stored chunk length must match a single payload.');
        self::assertSame(str_repeat($stored[0], self::PAYLOAD_LENGTH), $stored, 'Stored chunk must not be a torn write.');
    }

    public function testConcurrentWritesToDistinctChunksAllLand(): void
    {
        $this->createSession(totalChunks: self::WORKER_COUNT);
        $start = microtime(true);

        $results = $this->runWorkers(range(0, self::WORKER_COUNT - 1));

        $elapsed = microtime(true) - $start;
        $written = array_keys($results, 'WRITTEN', true);
        $rejected = array_keys($results, 'REJECTED', true);

        self::assertCount(self::WORKER_COUNT, $written, \sprintf('Every distinct-chunk write must land, got: %s', implode(', ', $results)));
        self::assertCount(0, $rejected, \sprintf('No distinct-chunk write may be falsely rejected, got: %s', implode(', ', $results)));

        for ($i = 0; $i < self::WORKER_COUNT; ++$i) {
            $stored = (string) file_get_contents($this->tempDir.'/'.$this->uploadId.'/chunk_'.$i);
            self::assertSame(str_repeat(\chr(65 + $i % 26), self::PAYLOAD_LENGTH), $stored, \sprintf('Chunk %d must hold exactly its worker payload.', $i));
        }

        // A generous ceiling: it catches a hang without being timing-fragile.
        self::assertLessThan(20.0, $elapsed, \sprintf('Concurrent distinct-chunk writes took %.2fs -- suspiciously long, possible serialization or deadlock.', $elapsed));
    }

    private function createSession(int $totalChunks): void
    {
        $this->filesystem->mkdir($this->tempDir.'/'.$this->uploadId);
        $this->filesystem->dumpFile(
            $this->tempDir.'/'.$this->uploadId.'/metadata.json',
            json_encode([
                'filename' => 'concurrent.bin',
                'fileSize' => $totalChunks * self::PAYLOAD_LENGTH,
                'mimeType' => 'application/octet-stream',
                'totalChunks' => $totalChunks,
                'createdAt' => time(),
                'uploader' => 'default',
            ], \JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @param list<int> $chunkIndexes
     *
     * @return array<int, string> the outcome reported by each worker
     */
    private function runWorkers(array $chunkIndexes): array
    {
        $barrierFile = $this->root.'/barrier';
        $processes = [];
        $pipes = [];

        foreach ($chunkIndexes as $i => $chunkIndex) {
            $process = proc_open([
                \PHP_BINARY,
                __DIR__.'/Fixtures/concurrent_chunk_worker.php',
                __DIR__.'/../vendor/autoload.php',
                $this->tempDir,
                $this->root.'/files',
                $this->uploadId,
                (string) $chunkIndex,
                \chr(65 + $i % 26),
                (string) self::PAYLOAD_LENGTH,
                $barrierFile,
            ], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $procPipes);
            self::assertIsResource($process, 'Failed to spawn worker process.');
            $processes[$i] = $process;
            $pipes[$i] = $procPipes;
        }

        // Let every worker reach its barrier spin before releasing them together.
        usleep(200_000);
        $this->filesystem->touch($barrierFile);

        $results = [];
        foreach ($processes as $i => $process) {
            $results[$i] = trim((string) stream_get_contents($pipes[$i][1]));
            fclose($pipes[$i][1]);
            fclose($pipes[$i][2]);
            proc_close($process);
        }

        $errors = array_filter($results, static fn (string $r) => str_starts_with($r, 'ERROR:'));
        self::assertSame([], $errors, 'Worker error(s): '.implode(' | ', $errors));

        return $results;
    }
}
