<?php

/*
 * Synchronizes each Symfony UX package's `.gitattributes` and `.gitignore` files.
 *
 * The content is fully regenerated from the filesystem: a rule only emits its
 * line when its target file or directory exists in the package. This keeps
 * every `.gitattributes` and `.gitignore` deterministic and consistent across packages.
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
$gitattributesRules = [
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
    ['/kits/*/*/tests', 'glob'],
    ['/phpstan.dist.neon', 'file'],
    ['/phpunit.dist.xml', 'file'],
    ['/phpunit.xml.dist', 'file'],
    ['/tests', 'dir'],
];

/**
 * Ordered rule table. Each entry is [pattern, type, target]; the line is emitted
 * only when the target exists, since the ignored path itself is generated.
 */
$gitignoreRules = [
    ['/assets/node_modules/', 'file', '/assets/package.json'],
    ['/assets/.vitest/', 'file', '/assets/vitest.config.mjs'],
    ['/config/reference.php', 'always', null],
    ['/config/schema.json', 'always', null],
    ['/vendor/', 'always', null],
    ['/composer.lock', 'always', null],
    ['/phpunit.xml', 'always', null],
    ['/.phpunit.cache', 'always', null],
    ['/.php-cs-fixer.cache', 'always', null],
    ['/.twig-cs-fixer.cache', 'file', '/.twig-cs-fixer.dist.php'],
    ['/var', 'always', null],
];

/**
 * Lines appended to the `.gitignore` of a single package, keyed by its path.
 */
$gitignoreExtras = [
    'src/StimulusBundle' => ['/tests/fixtures/var'],
    'src/Turbo' => ['/drivers/'],
];

$exists = static fn (string $target, string $type): bool => match ($type) {
    'always' => true,
    'file' => is_file($target),
    'dir' => is_dir($target),
    'glob' => [] !== (glob($target, \GLOB_ONLYDIR) ?: []),
};

$generateGitattributes = static function (string $packageDir) use ($gitattributesRules, $exists): string {
    $lines = [];

    foreach ($gitattributesRules as [$pattern, $type]) {
        if ($exists($packageDir.$pattern, $type)) {
            $lines[] = $pattern.' export-ignore';
        }
    }

    foreach (glob($packageDir.'/*.png') ?: [] as $png) {
        $lines[] = '/'.basename($png).' export-ignore';
    }

    return implode("\n", $lines)."\n";
};

$generateGitignore = static function (string $packageDir, string $relativeDir) use ($gitignoreRules, $gitignoreExtras, $exists): string {
    $lines = [];

    foreach ($gitignoreRules as [$pattern, $type, $target]) {
        if ($exists($packageDir.$target, $type)) {
            $lines[] = $pattern;
        }
    }

    foreach ($gitignoreExtras[$relativeDir] ?? [] as $pattern) {
        $lines[] = $pattern;
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
    $relativeDir = substr(realpath($packageDir), strlen($rootDir) + 1);

    $files = [
        '.gitattributes' => $generateGitattributes($packageDir),
        '.gitignore' => $generateGitignore($packageDir, $relativeDir),
    ];

    foreach ($files as $name => $expected) {
        $file = $packageDir.'/'.$name;
        $current = is_file($file) ? file_get_contents($file) : '';

        if ($current === $expected) {
            continue;
        }

        file_put_contents($file, $expected);
        $written[] = $relativeDir.'/'.$name;
    }
}

if ($written) {
    echo "Updated:\n";
    foreach ($written as $relative) {
        echo "  {$relative}\n";
    }
} else {
    echo "All .gitattributes and .gitignore are already in sync.\n";
}
