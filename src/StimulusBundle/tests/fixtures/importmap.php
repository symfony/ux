<?php

/*
 * This file is part of the Symfony StimulusBundle package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'app' => [
        'path' => 'app.js',
        'preload' => true,
    ],
    '@hotwired/stimulus' => [
        'url' => 'https://ga.jspm.io/npm:@hotwired/stimulus@3.2.1/dist/stimulus.js',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => '@symfony/stimulus-bundle/loader.js',
        'preload' => true,
    ],
    'needed-vendor' => [
        'url' => 'https://cdn.jsdelivr.net/npm/needed-vendor@3.2.0/dist/needed-vendor+esm',
    ],
    '@scoped/needed-vendor' => [
        'url' => 'https://cdn.jsdelivr.net/npm/@scoped/needed-vendor@1.2.3',
    ],
];
