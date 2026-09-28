<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 *
 * @return array<string, array{    // Import name as key, description of the imported file as value
 *     path: string,               // Logical, relative or absolute path to the file
 *     type?: 'js'|'css'|'json',   // Type of the file, defaults to 'js'
 *     entrypoint?: bool,          // Whether the file is an entrypoint, for 'js' only
 * }|array{
 *     version: string,            // Version of the remote package
 *     package_specifier?: string, // Remote "package-name/path" specifier, defaults to the import name
 *     type?: 'js'|'css'|'json',
 *     entrypoint?: bool,
 * }>
 */
return [
    '@hotwired/stimulus' => ['version' => '3.2.2'],
    'tw-animate-css/dist/tw-animate.css' => ['version' => '1.4.0', 'type' => 'css'],
    'shadcn/dist/tailwind.css' => ['version' => '4.21.0', 'type' => 'css'],
    'flowbite' => ['version' => '4.0.2'],
    'bootstrap' => ['version' => '5.3.8'],
    'bootstrap/dist/css/bootstrap.min.css' => ['version' => '5.3.8', 'type' => 'css'],
    '@floating-ui/dom' => ['version' => '1.8.0'],
    'embla-carousel' => ['version' => '8.6.0'],
    'embla-carousel-autoplay' => ['version' => '8.6.0'],
    '@popperjs/core' => ['version' => '2.11.8'],
    'flowbite-datepicker' => ['version' => '2.0.0'],
    '@floating-ui/core' => ['version' => '1.8.0'],
    '@floating-ui/utils' => ['version' => '0.2.12'],
    '@floating-ui/utils/dom' => ['version' => '0.2.12'],
    'flowbite/dist/flowbite.min.css' => ['version' => '4.0.2', 'type' => 'css'],
];
