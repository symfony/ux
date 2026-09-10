<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Isolation;

/**
 * Boots UXImageBundle in a child PHP process with a chosen set of optional
 * packages hidden from the autoloader, so the "package is not installed"
 * branches of the container-compile guards can be exercised.
 *
 * In-process this is impossible: league/flysystem, intervention/image,
 * doctrine/dbal and symfony/ux-twig-component all live in this bundle's
 * require-dev (the rest of the suite needs them), so their classes are always
 * autoloadable and class_exists()/interface_exists() always return true. The
 * child process neutralises the relevant PSR-4 prefixes on Composer's
 * ClassLoader before the kernel boots; see tests/Isolation/isolated_boot.php
 * for the mechanism.
 *
 * The trade-off is speed: each call spawns a real PHP process that boots a
 * kernel (~0.5s). Keep the number of isolated cases small and tag them so they
 * can be split out of a fast test group if needed.
 */
trait IsolatedBootTrait
{
    /**
     * Options: 'ux_image' (extension config array), 'action' ('boot'|'process'),
     * 'has' (list of service ids to probe), 'twig_component_enabled' (bool).
     *
     * @param list<string>         $hidden  PSR-4 prefixes to hide (e.g. 'League\\Flysystem\\')
     * @param array<string, mixed> $options
     *
     * @return array{status: string, class?: string, message?: string, has?: array<string, bool>, process?: array{variant_formats: list<string>, fallback_src: string, source_count: int}|null, component_registered?: bool|null}
     */
    private function bootInIsolation(array $hidden, array $options = []): array
    {
        $script = \dirname(__DIR__).'/Isolation/isolated_boot.php';

        $input = json_encode([
            'hidden' => $hidden,
            'ux_image' => $options['ux_image'] ?? [],
            'action' => $options['action'] ?? 'boot',
            'has' => $options['has'] ?? [],
            'twig_component_enabled' => $options['twig_component_enabled'] ?? true,
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
            /** @var array{status: string, class?: string, message?: string, has?: array<string, bool>, process?: array{variant_formats: list<string>, fallback_src: string, source_count: int}|null, component_registered?: bool|null} $decoded */
            $decoded = json_decode($line, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            self::fail(\sprintf("Isolated boot output was not JSON: %s\nraw: %s\nstderr:\n%s", $e->getMessage(), $line, $stderr));
        }

        return $decoded;
    }
}
