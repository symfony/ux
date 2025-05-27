<?php

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Finder\Finder;

$finder = (new Finder())
    ->in([__DIR__.'/../src/*/', __DIR__.'/../src/*/src/Bridge/*/'])
    ->depth(0)
    ->name('composer.json')
;

// 1. Find all UX packages
$uxPackages = [];
foreach ($finder as $composerFile) {
    $json = file_get_contents($composerFile->getPathname());
    if (null === $packageData = json_decode($json, true)) {
        passthru(sprintf('composer validate %s', $composerFile->getPathname()));
        exit(1);
    }

    if (str_starts_with($composerFile->getPathname(), __DIR__ . '/../src/')) {
        $packageName = $packageData['name'];

        $uxPackages[] = [
            'path' => realpath($composerFile->getPath()),
        ];
    }
}

echo json_encode($uxPackages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
