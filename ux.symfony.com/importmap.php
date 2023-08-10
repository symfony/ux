<?php
/**
 * Returns the import map for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "preload" set to true for any modules that are loaded on the initial
 *     page load to help the browser download them earlier.
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => 'app.js',
        'preload' => true,
    ],
    '@symfony/stimulus-bundle' => [
        'path' => '@symfony/stimulus-bundle/loader.js',
        'preload' => true,
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
    'bootstrap' => [
        'url' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/+esm',
    ],
    '@popperjs/core' => [
        'url' => 'https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/+esm',
    ],
    'stimulus-clipboard' => [
        'url' => 'https://cdn.jsdelivr.net/npm/stimulus-clipboard@3.3.0/+esm',
    ],
    '@hotwired/stimulus' => [
        'url' => 'https://cdn.jsdelivr.net/npm/@hotwired/stimulus@3.2.1/+esm',
    ],
    'tom-select' => [
        'url' => 'https://cdn.jsdelivr.net/npm/tom-select@2.2.2/+esm',
    ],
    'react' => [
        'url' => 'https://cdn.jsdelivr.net/npm/react@18.2.0/+esm',
    ],
    'react-dom' => [
        'url' => 'https://cdn.jsdelivr.net/npm/react-dom@18.2.0/+esm',
    ],
    'vue' => [
        'url' => 'https://cdn.jsdelivr.net/npm/vue/dist/vue.esm-bundler.js/+esm',
    ],
    'swup' => [
        'url' => 'https://cdn.jsdelivr.net/npm/swup@3.0.6/+esm',
    ],
    'delegate-it' => [
        'url' => 'https://cdn.jsdelivr.net/npm/delegate-it@6.0.1/+esm',
    ],
    '@swup/debug-plugin' => [
        'url' => 'https://cdn.jsdelivr.net/npm/@swup/debug-plugin@3.0.0/+esm',
    ],
    '@swup/fade-theme' => [
        'url' => 'https://cdn.jsdelivr.net/npm/@swup/fade-theme@1.0.5/+esm',
    ],
    '@swup/forms-plugin' => [
        'url' => 'https://cdn.jsdelivr.net/npm/@swup/forms-plugin@2.0.1/+esm',
    ],
    '@swup/slide-theme' => [
        'url' => 'https://cdn.jsdelivr.net/npm/@swup/slide-theme@1.0.5/+esm',
    ],
    '@swup/plugin' => [
        'url' => 'https://cdn.jsdelivr.net/npm/@swup/plugin@2.0.2/+esm',
    ],
    '@hotwired/turbo' => [
        'url' => 'https://cdn.jsdelivr.net/npm/@hotwired/turbo@7.3.0/+esm',
    ],
    'typed.js' => [
        'url' => 'https://cdn.jsdelivr.net/npm/typed.js@2.0.16/+esm',
    ],
    'snarkdown' => [
        'url' => 'https://cdn.jsdelivr.net/npm/snarkdown@2.0.0/+esm',
    ],
    'chart.js/auto' => [
        'url' => 'https://cdn.jsdelivr.net/npm/chart.js@4.3.0/auto/+esm',
    ],
    'cropperjs' => [
        'url' => 'https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/+esm',
    ],
    'svelte/transition' => [
        'url' => 'https://cdn.jsdelivr.net/npm/svelte@3.59.1/transition/+esm',
    ],
    'svelte/animate' => [
        'url' => 'https://cdn.jsdelivr.net/npm/svelte@3.59.1/animate/+esm',
    ],
    'svelte/internal' => [
        'url' => 'https://cdn.jsdelivr.net/npm/svelte@3.59.1/internal/+esm',
    ],
    'highlight.js/lib/core' => [
        'url' => 'https://cdn.jsdelivr.net/npm/highlight.js@11.8.0/lib/core/+esm',
    ],
    'highlight.js/lib/languages/javascript' => [
        'url' => 'https://cdn.jsdelivr.net/npm/highlight.js@11.8.0/lib/languages/javascript/+esm',
    ],
    'intl-messageformat' => [
        'url' => 'https://cdn.jsdelivr.net/npm/intl-messageformat@10.3.5/+esm',
    ],
];
