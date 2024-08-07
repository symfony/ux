<?php

/**
 * Updates the composer.json files to use the local version of the Symfony UX packages.
 */

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Finder\Finder;

$finder = (new Finder())
    ->in([__DIR__.'/../src/*/', __DIR__.'/../src/*/src/Bridge/*/'])
    ->depth(0)
    ->name('composer.json')
;

foreach ($finder as $composerFile) {
    $json = file_get_contents($composerFile->getPathname());
    if (null === $packageData = json_decode($json, true)) {
        passthru(sprintf('composer validate %s', $composerFile->getPathname()));
        exit(1);
    }

    $repositories = [];

    if (isset($packageData['require']['symfony/ux-twig-component'])
        || isset($packageData['require-dev']['symfony/ux-twig-component'])
    ) {
        $repositories[] = [
            'type' => 'path',
            'url' => '../TwigComponent',
        ];
        $key = isset($packageData['require']['symfony/ux-twig-component']) ? 'require' : 'require-dev';
        $packageData[$key]['symfony/ux-twig-component'] = '@dev';
    }

    if (isset($packageData['require']['symfony/stimulus-bundle'])
        || isset($packageData['require-dev']['symfony/stimulus-bundle'])
    ) {
        $repositories[] = [
            'type' => 'path',
            'url' => '../StimulusBundle',
        ];
        $key = isset($packageData['require']['symfony/stimulus-bundle']) ? 'require' : 'require-dev';
        $packageData[$key]['symfony/stimulus-bundle'] = '@dev';
    }
    
    if (isset($packageData['require']['symfony/ux-map'])
        || isset($packageData['require-dev']['symfony/ux-map'])
    ) {
        $repositories[] = [
            'type' => 'path',
            'url' => '../../../',
        ];
        $key = isset($packageData['require']['symfony/ux-map']) ? 'require' : 'require-dev';
        $packageData[$key]['symfony/ux-map'] = '@dev';
    }
    
    if ($repositories) {
        $packageData['repositories'] = $repositories;
    }

    $json = json_encode($packageData, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
    file_put_contents($composerFile->getPathname(), $json."\n");
}
