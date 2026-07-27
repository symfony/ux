<?php

/*
 * Synchronizes each Symfony UX package's `.gitattributes` file.
 *
 * The content is fully regenerated from the filesystem: a rule only emits its
 * line when the matching file or directory exists in the package. This keeps
 * every `.gitattributes` deterministic and consistent across packages.
 *
 * Usage: php .github/sync-packages.php
 */

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Finder\Finder;

if ('cli' !== \PHP_SAPI) {
    echo "This script can only be run from the command line.\n";
    exit(1);
}

$rootDir = dirname(__DIR__);

/**
 * Ordered rule table. Each entry is [pattern, type]; the line is emitted only
 * when the target exists. The array order is the canonical file order.
 */
$rules = [
    ['/.git*', 'always'],
    ['/.symfony.bundle.yaml', 'file'],
    ['/.php-cs-fixer.dist.php', 'file'],
    ['/assets/src', 'dir'],
    ['/assets/test', 'dir'],
    ['/assets/playwright.config.ts', 'file'],
    ['/assets/tsconfig.json', 'file'],
    ['/assets/vitest.config.mjs', 'file'],
    ['/CONTRIBUTING.md', 'file'],
    ['/doc', 'dir'],
    ['/docker-compose.yml', 'file'],
    ['/phpstan.dist.neon', 'file'],
    ['/phpunit.dist.xml', 'file'],
    ['/phpunit.xml.dist', 'file'],
    ['/tests', 'dir'],
];

$generate = static function (string $packageDir) use ($rules): string {
    $lines = [];

    foreach ($rules as [$pattern, $type]) {
        $target = $packageDir.$pattern;
        $exists = match ($type) {
            'always' => true,
            'file' => is_file($target),
            'dir' => is_dir($target),
        };

        if ($exists) {
            $lines[] = $pattern.' export-ignore';
        }
    }

    foreach (glob($packageDir.'/*.png') ?: [] as $png) {
        $lines[] = '/'.basename($png).' export-ignore';
    }

    return implode("\n", $lines)."\n";
};

$finder = new Finder()
    ->in([__DIR__.'/../src/*/', __DIR__.'/../src/*/src/Bridge/*/'])
    ->depth(0)
    ->name('composer.json')
    ->sortByName()
;

$written = [];

foreach ($finder as $composerFile) {
    $packageDir = $composerFile->getPath();
    $file = $packageDir.'/.gitattributes';

    $expected = $generate($packageDir);
    $current = is_file($file) ? file_get_contents($file) : '';

    if ($current === $expected) {
        continue;
    }

    file_put_contents($file, $expected);
    $written[] = substr(realpath($packageDir), strlen($rootDir) + 1);
}

if ($written) {
    echo "Updated .gitattributes for:\n";
    foreach ($written as $relative) {
        echo "  {$relative}\n";
    }
} else {
    echo "All .gitattributes are already in sync.\n";
}
