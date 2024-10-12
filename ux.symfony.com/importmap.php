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
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    'app-react' => [
        'path' => './assets/react/app-react.js',
        'entrypoint' => true,
    ],
    'app-svelte' => [
        'path' => './assets/svelte/app-svelte.js',
        'entrypoint' => true,
    ],
    'app-vue' => [
        'path' => './assets/vue/app-vue.js',
        'entrypoint' => true,
    ],
    'demos/live-memory' => [
        'path' => './assets/demos/live-memory.js',
        'entrypoint' => true,
    ],
    '@symfony/stimulus-bundle' => [
        'path' => '@symfony/stimulus-bundle/loader.js',
    ],
    '@symfony/ux-react' => [
        'path' => '@symfony/ux-react/loader.js',
    ],
    '@symfony/ux-vue' => [
        'path' => '@symfony/ux-vue/loader.js',
    ],
    '@symfony/ux-svelte' => [
        'path' => '@symfony/ux-svelte/loader.js',
    ],
    '@symfony/ux-translator' => [
        'path' => '@symfony/ux-translator/translator_controller.js',
    ],
    '@app/translations' => [
        'path' => 'var/translations/index.js',
    ],
    '@app/translations/configuration' => [
        'path' => 'var/translations/configuration.js',
    ],
    '@symfony/ux-live-component' => [
        'path' => './vendor/symfony/ux-live-component/assets/dist/live_controller.js',
    ],
    'bootstrap' => [
        'version' => '5.3.3',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'stimulus-clipboard' => [
        'version' => '4.0.1',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    'tom-select' => [
        'version' => '2.3.1',
    ],
    'react' => [
        'version' => '18.3.1',
    ],
    'react-dom' => [
        'version' => '18.3.1',
    ],
    'vue' => [
        'version' => '3.4.31',
        'package_specifier' => 'vue/dist/vue.esm-bundler.js',
    ],
    'swup' => [
        'version' => '3.1.1',
    ],
    'delegate-it' => [
        'version' => '6.1.0',
    ],
    '@swup/debug-plugin' => [
        'version' => '3.0.0',
    ],
    '@swup/fade-theme' => [
        'version' => '1.0.5',
    ],
    '@swup/forms-plugin' => [
        'version' => '2.0.1',
    ],
    '@swup/slide-theme' => [
        'version' => '1.0.5',
    ],
    '@swup/plugin' => [
        'version' => '2.0.3',
    ],
    '@hotwired/turbo' => [
        'version' => '8.0.4',
    ],
    'typed.js' => [
        'version' => '2.1.0',
    ],
    'snarkdown' => [
        'version' => '2.0.0',
    ],
    'cropperjs' => [
        'version' => '1.6.2',
    ],
    'svelte/transition' => [
        'version' => '4.2.18',
    ],
    'svelte/animate' => [
        'version' => '4.2.18',
    ],
    'svelte/internal' => [
        'version' => '3.59.2',
    ],
    'intl-messageformat' => [
        'version' => '10.5.14',
    ],
    '@vue/runtime-dom' => [
        'version' => '3.4.31',
    ],
    '@vue/runtime-core' => [
        'version' => '3.4.31',
    ],
    '@vue/shared' => [
        'version' => '3.4.31',
    ],
    '@vue/reactivity' => [
        'version' => '3.4.31',
    ],
    '@vue/compiler-dom' => [
        'version' => '3.4.31',
    ],
    '@vue/compiler-core' => [
        'version' => '3.4.31',
    ],
    'tslib' => [
        'version' => '2.6.3',
    ],
    '@formatjs/icu-messageformat-parser' => [
        'version' => '2.7.8',
    ],
    '@formatjs/icu-skeleton-parser' => [
        'version' => '1.8.2',
    ],
    '@formatjs/fast-memoize' => [
        'version' => '2.2.0',
    ],
    'tom-select/dist/css/tom-select.bootstrap5.css' => [
        'version' => '2.3.1',
        'type' => 'css',
    ],
    'cropperjs/dist/cropper.min.css' => [
        'version' => '1.6.2',
        'type' => 'css',
    ],
    'scheduler' => [
        'version' => '0.23.2',
    ],
    'tippy.js' => [
        'version' => '6.3.7',
    ],
    'tippy.js/dist/tippy.css' => [
        'version' => '6.3.7',
        'type' => 'css',
    ],
    'tippy.js/themes/translucent.css' => [
        'version' => '6.3.7',
        'type' => 'css',
    ],
    'path-to-regexp' => [
        'version' => '6.2.1',
    ],
    '@swup/theme' => [
        'version' => '2.1.0',
    ],
    '@kurkle/color' => [
        'version' => '0.3.2',
    ],
    'chart.js' => [
        'version' => '4.4.3',
    ],
    'leaflet' => [
        'version' => '1.9.4',
    ],
    'leaflet/dist/leaflet.min.css' => [
        'version' => '1.9.4',
        'type' => 'css',
    ],
    '@symfony/ux-leaflet-map' => [
        'path' => './vendor/symfony/ux-leaflet-map/assets/dist/map_controller.js',
    ],
];
