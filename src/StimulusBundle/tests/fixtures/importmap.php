<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'app' => [
        'path' => 'app.js',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.1',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => '@symfony/stimulus-bundle/loader.js',
    ],
    'needed-vendor' => [
        'version' => '3.2.0',
    ],
    'needed-vendor/file.css' => [
        'version' => '3.2.0',
        'type' => 'css',
    ],
    '@scoped/needed-vendor' => [
        'version' => '1.2.3',
    ],
    '@scoped/needed-vendor/the/file2.css' => [
        'version' => '1.2.3',
        'type' => 'css',
    ],
];
