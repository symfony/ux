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

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Proves the symfony/lock guard in {@see \Symfony\UX\Upload\Uploader::storeChunk()}
 * closes the check-then-write overwrite race under *real* concurrency.
 *
 * Several OS processes are launched and released together on a single
 * (uploadId, chunkIndex). Without the lock, two of them can both pass the
 * "already uploaded?" check and both write, so more than one reports success and
 * the stored bytes are non-deterministic. With the lock, exactly one write wins
 * and every other request is rejected cleanly as a duplicate.
 *
 * @group concurrency
 */
#[Group('concurrency')]
final class ConcurrentChunkWriteTest extends TestCase
{
    private Filesystem $filesystem;
    private string $root;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->root = sys_get_temp_dir().'/ux_upload_concurrency_'.bin2hex(random_bytes(6));
        $this->filesystem->mkdir($this->root);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->root);
    }

    public function testConcurrentWritesToSameChunkYieldExactlyOneWinner(): void
    {
        $tempDir = $this->root.'/tmp';
        $directory = $this->root.'/files';
        $uploadId = bin2hex(random_bytes(8));
        $chunkIndex = 0;
        $payloadLen = 128 * 1024;
        $barrierFile = $this->root.'/barrier';

        // Create the upload session (metadata + directory) the workers will target.
        $this->filesystem->mkdir($tempDir.'/'.$uploadId);
        $this->filesystem->dumpFile(
            $tempDir.'/'.$uploadId.'/metadata.json',
            json_encode([
                'filename' => 'concurrent.bin',
                'fileSize' => $payloadLen,
                'mimeType' => 'application/octet-stream',
                'totalChunks' => 1,
                'createdAt' => time(),
                'uploader' => 'default',
            ], \JSON_THROW_ON_ERROR),
        );

        $worker = __DIR__.'/Fixtures/concurrent_chunk_worker.php';
        $autoload = __DIR__.'/../vendor/autoload.php';

        $workerCount = 6;
        $processes = [];
        $pipes = [];

        for ($i = 0; $i < $workerCount; ++$i) {
            $cmd = [
                \PHP_BINARY,
                $worker,
                $autoload,
                $tempDir,
                $directory,
                $uploadId,
                (string) $chunkIndex,
                \chr(65 + $i), // distinct payload byte per worker
                (string) $payloadLen,
                $barrierFile,
            ];

            $process = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $procPipes);
            self::assertIsResource($process, 'Failed to spawn worker process.');
            $processes[$i] = $process;
            $pipes[$i] = $procPipes;
        }

        // Give every worker a moment to reach its barrier spin, then release them.
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

        $written = array_keys($results, 'WRITTEN', true);
        $rejected = array_keys($results, 'REJECTED', true);

        self::assertCount(1, $written, \sprintf('Exactly one write must win, got: %s', implode(', ', $results)));
        self::assertCount($workerCount - 1, $rejected, 'Every loser must be rejected as a duplicate.');

        // The stored chunk must equal exactly one worker's payload, never a torn mix.
        $stored = (string) file_get_contents($tempDir.'/'.$uploadId.'/chunk_'.$chunkIndex);
        self::assertSame($payloadLen, \strlen($stored), 'Stored chunk length must match a single payload.');
        self::assertSame(str_repeat($stored[0], $payloadLen), $stored, 'Stored chunk must not be a torn write.');
    }
}
