<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\Upload\Exception\InvalidArgumentException;
use Symfony\UX\Upload\Storage\LocalStorage;
use Symfony\UX\Upload\Uploader;
use Symfony\UX\Upload\Url\UploadUrlGeneratorInterface;

/*
 * Standalone worker used by ConcurrentChunkWriteTest to provoke a real
 * (multi-process) race on a single (uploadId, chunkIndex). It waits on a barrier
 * file so every worker fires its storeChunk() call at roughly the same instant,
 * then reports whether its write won ("WRITTEN") or was rejected as a duplicate
 * ("REJECTED"). Any other outcome is reported as "ERROR:<message>".
 */

require $argv[1];   // vendor/autoload.php

$tempDir = $argv[2];
$directory = $argv[3];
$uploadId = $argv[4];
$chunkIndex = (int) $argv[5];
$payload = str_repeat($argv[6], (int) $argv[7]);
$barrierFile = $argv[8];

$urlGenerator = new class implements UploadUrlGeneratorInterface {
    public function generateUploadUrl(string $uploadId): string
    {
        return '';
    }

    public function verifyRequest(Request $request): bool
    {
        return true;
    }
};

$uploader = new Uploader(
    new LocalStorage($directory, $tempDir),
    $urlGenerator,
    new EventDispatcher(),
);

// Spin on the barrier so all workers are released together.
$deadline = microtime(true) + 10.0;
while (!file_exists($barrierFile)) {
    if (microtime(true) > $deadline) {
        echo 'ERROR:barrier timeout';
        exit(1);
    }
    usleep(200);
}

try {
    $uploader->storeChunk($uploadId, $chunkIndex, $payload);
    echo 'WRITTEN';
} catch (InvalidArgumentException $e) {
    echo 'REJECTED';
} catch (Throwable $e) {
    echo 'ERROR:'.$e->getMessage();
}
