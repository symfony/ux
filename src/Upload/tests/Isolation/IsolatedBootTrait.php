<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Isolation;

/**
 * Boots UXUploadBundle in a child PHP process with a chosen set of optional
 * packages hidden from the autoloader, so the "package is not installed"
 * branches of the container-compile guards can be exercised.
 *
 * In-process this is impossible: league/flysystem, symfony/console and
 * symfony/security-csrf all live in this bundle's require-dev (the rest of the
 * suite needs them), so their classes are always autoloadable and
 * class_exists()/interface_exists() always return true. The child process
 * neutralises the relevant PSR-4 prefixes on Composer's ClassLoader before the
 * kernel boots; see tests/Isolation/isolated_boot.php for the mechanism.
 *
 * The trade-off is speed: each call spawns a real PHP process that boots a
 * kernel (~0.5s). Keep the number of isolated cases small and tag them so they
 * can be split out of a fast test group if needed.
 */
trait IsolatedBootTrait
{
    /**
     * Options: 'ux_upload' (extension config array), 'csrf_protection' (bool),
     * 'action' ('boot'|'create_form'|'run_upload'), 'has' (list of service ids to probe).
     *
     * @param list<string>         $hidden  PSR-4 prefixes to hide (e.g. 'League\\Flysystem\\')
     * @param array<string, mixed> $options
     *
     * @return array{status: string, class?: string, message?: string, csrf_token?: string|null, has?: array<string, bool>, upload?: array{uploadId: string, filename: string, size: int}|null}
     */
    private function bootInIsolation(array $hidden, array $options = []): array
    {
        $script = \dirname(__DIR__).'/Isolation/isolated_boot.php';

        $input = json_encode([
            'hidden' => $hidden,
            'csrf_protection' => $options['csrf_protection'] ?? false,
            'ux_upload' => $options['ux_upload'] ?? [],
            'action' => $options['action'] ?? 'boot',
            'has' => $options['has'] ?? [],
        ], \JSON_THROW_ON_ERROR);

        $process = proc_open(
            [\PHP_BINARY, $script],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );

        if (!\is_resource($process)) {
            self::fail('Unable to spawn the isolated boot process.');
        }

        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $line = trim((string) $stdout);
        if ('' === $line) {
            self::fail(\sprintf("Isolated boot produced no output.\nstderr:\n%s", $stderr));
        }

        try {
            /** @var array{status: string, class?: string, message?: string, csrf_token?: string|null, has?: array<string, bool>} $decoded */
            $decoded = json_decode($line, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            self::fail(\sprintf("Isolated boot output was not JSON: %s\nraw: %s\nstderr:\n%s", $e->getMessage(), $line, $stderr));
        }

        return $decoded;
    }
}
