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
 * Proves that concurrent writes to *distinct* chunk indices of the *same* upload
 * all land: no false duplicate rejection, no deadlock.
 *
 * The sibling {@see ConcurrentChunkWriteTest} proves correctness on the *same*
 * chunk (exactly one winner, the rest rejected). This test covers the other half:
 * every distinct index must be stored. The per-upload lifecycle lock taken in
 * storeChunk() serializes these writes, so what is checked here is that the
 * serialization stays correct under load, not that the writes overlap.
 *
 * @group concurrency
 */
#[Group('concurrency')]
final class ConcurrentDistinctChunkWriteTest extends TestCase
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

    public function testConcurrentWritesToDistinctChunksAllLand()
    {
        $tempDir = $this->root.'/tmp';
        $directory = $this->root.'/files';
        $uploadId = bin2hex(random_bytes(8));
        $workerCount = 6;
        $payloadLen = 128 * 1024;
        $barrierFile = $this->root.'/barrier';

        // Create the upload session with room for one chunk per worker.
        $this->filesystem->mkdir($tempDir.'/'.$uploadId);
        $this->filesystem->dumpFile(
            $tempDir.'/'.$uploadId.'/metadata.json',
            json_encode([
                'filename' => 'distinct.bin',
                'fileSize' => $workerCount * $payloadLen,
                'mimeType' => 'application/octet-stream',
                'totalChunks' => $workerCount,
                'createdAt' => time(),
                'uploader' => 'default',
            ], \JSON_THROW_ON_ERROR),
        );

        $worker = __DIR__.'/Fixtures/concurrent_chunk_worker.php';
        $autoload = __DIR__.'/../vendor/autoload.php';

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
                (string) $i,      // distinct chunk index per worker
                \chr(65 + $i),    // distinct payload byte per worker
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
        $start = microtime(true);
        $this->filesystem->touch($barrierFile);

        $results = [];
        foreach ($processes as $i => $process) {
            $results[$i] = trim((string) stream_get_contents($pipes[$i][1]));
            fclose($pipes[$i][1]);
            fclose($pipes[$i][2]);
            proc_close($process);
        }
        $elapsed = microtime(true) - $start;

        $errors = array_filter($results, static fn (string $r) => str_starts_with($r, 'ERROR:'));
        self::assertSame([], $errors, 'Worker error(s): '.implode(' | ', $errors));

        // Distinct indices take distinct lock keys: every write must win, none rejected.
        $written = array_keys($results, 'WRITTEN', true);
        $rejected = array_keys($results, 'REJECTED', true);
        self::assertCount($workerCount, $written, \sprintf('Every distinct-chunk write must land, got: %s', implode(', ', $results)));
        self::assertCount(0, $rejected, \sprintf('No distinct-chunk write may be falsely rejected, got: %s', implode(', ', $results)));

        // Each chunk landed intact with its own worker's payload (no cross-chunk torn writes).
        for ($i = 0; $i < $workerCount; ++$i) {
            $stored = (string) file_get_contents($tempDir.'/'.$uploadId.'/chunk_'.$i);
            self::assertSame(str_repeat(\chr(65 + $i), $payloadLen), $stored, \sprintf('Chunk %d must hold exactly its worker payload.', $i));
        }

        // Loose upper bound: the point is "no deadlock", not precise timing. Parallel
        // per-chunk writes of a few hundred KB complete in well under a second; a
        // generous ceiling catches a hang without being timing-fragile.
        self::assertLessThan(20.0, $elapsed, \sprintf('Concurrent distinct-chunk writes took %.2fs -- suspiciously long, possible serialization or deadlock.', $elapsed));
    }
}
