<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use App\Model\UxPackage;

class UxPackageRepository
{
    /**
     * @return array<UxPackage>
     */
    public function findAll(?string $query = null): array
    {
        $packages = [
            new UxPackage(
                'icons',
                'UX Icons',
                'app_icons',
                '#fff',
                'linear-gradient(to bottom right, cyan, purple)',
                'SVG icons made easy',
                'Render SVG icons seamlessly from your Twig templates.',
                'I need to render SVG icons.',
                'icons.svg',
            ),

            new UxPackage(
                'map',
                'UX Map',
                'app_map',
                '#1BA980',
                'linear-gradient(to bottom right, #1BA980, #209127 75%, #C0CB2A)',
                'Interactive Maps',
                'Render interactive Maps in PHP with Leaflet or Google Maps.',
                'I need to display markers on a Map.',
                'map.svg',
            ),

            new UxPackage(
                'twig-component',
                'Twig Components',
                'app_twig_component',
                '#7FA020',
                'linear-gradient(95deg, #7FA020 -5%, #A1C94E 105%)',
                'Render Reusable UI Elements',
                'Create PHP classes that can render themselves',
                'I need to create PHP classes that render'
            ),

            new UxPackage(
                'live-component',
                'Live Components',
                'app_live_component',
                '#D98A11',
                'linear-gradient(124deg, #BF5421, #D98A11)',
                'Interactive UI in PHP & Twig',
                'Build dynamic interfaces with zero JavaScript',
                'I need Twig templates that update in real-time!'
            ),

            (new UxPackage(
                'turbo',
                'Turbo',
                'app_turbo',
                '#5920A0',
                'linear-gradient(95deg, #5920A0 -5%, #844EC9 105%)',
                'Single-page Symfony app',
                'Integration with Turbo for single-page-app and real-time experience',
                'I need to transform my app into an SPA!'
            ))
                ->setDocsLink('https://turbo.hotwired.dev/handbook/introduction', 'Documentation specifically for the Turbo JavaScript library.')
                ->setScreencastLink('https://symfonycasts.com/screencast/turbo', 'Go deep into all 3 parts of Turbo.'),

            (new UxPackage(
                'stimulus',
                'Stimulus',
                'app_stimulus',
                '#2EB17B',
                'linear-gradient(to bottom right, #3D9A89 5%, #2EB17B 80%)',
                'Central Bridge of Symfony UX',
                'Integration with Stimulus for HTML-powered controllers',
                null,
                'stimulus.svg',
                'symfony/stimulus-bundle',
            ))
            ->setOfficialDocsUrl('https://symfony.com/bundles/StimulusBundle')
            ->setScreencastLink('https://symfonycasts.com/screencast/stimulus', 'More than 40 videos to master Stimulus.'),

            new UxPackage(
                'autocomplete',
                'Autocomplete',
                'app_autocomplete',
                '#DF275E',
                'linear-gradient(95deg, #DF275E -5%, #E85995 105%)',
                'Ajax-powered Form Select',
                'Ajax-powered, auto-completable `select` elements',
                'I need an Ajax-autocomplete select field'
            ),

            new UxPackage(
                'translator',
                'Translator',
                'app_translator',
                '#2248D0',
                'linear-gradient(139deg, #2248D0 -20%, #00FFB2 113%)',
                'Symfony Translations in JavaScript',
                "Use Symfony's translations in JavaScript",
                'I need to translate strings in JavaScript',
                'translator.svg',
            ),

            (new UxPackage(
                'chartjs',
                'Chart.js',
                'app_chartjs',
                '#21A81E',
                'linear-gradient(95deg, #21A81E, #45DD42 108%)',
                'Interactive charts with Chart.js',
                'Easy charts with Chart.js',
                'I need to build a chart'
            ))
                ->setDocsLink('https://www.chartjs.org/', 'Chart.js documentation.'),

            (new UxPackage(
                'react',
                'React',
                'app_react',
                '#10A2CB',
                'linear-gradient(95deg, #10a2cb -5%, #42caf0 105%)',
                'Render React components from Twig',
                'Quickly render `<React />` components &amp; pass them props.',
                'I need to render React components from Twig'
            ))
                ->setDocsLink('https://reactjs.org/', 'Go deeper with the React docs.'),

            (new UxPackage(
                'vue',
                'Vue.js',
                'app_vue',
                '#35b67c',
                'linear-gradient(95deg, #35B67C -5%, #8CE3BC 105%)',
                'Render Vue components from Twig',
                'Quickly render `<Vue />` components &amp; pass them props.',
                null,
            ))
                ->setDocsLink('https://vuejs.org/', 'Go deeper with the Vue.js docs.'),

            (new UxPackage(
                'svelte',
                'Svelte',
                'app_svelte',
                '#FF3E00',
                'linear-gradient(115deg, #BE3030, #FF3E00)',
                'Render Svelte components from Twig',
                'Quickly render `<Svelte />` components &amp; pass them props.',
                null,
                'svelte.svg',
            ))
                ->setDocsLink('https://svelte.dev/', 'Go deeper with the Svelte docs.'),

            (new UxPackage(
                'cropperjs',
                'Image Cropper',
                'app_cropperjs',
                '#1E8FA8',
                'linear-gradient(136deg, #1E8FA8 -7%, #3FC0DC 105%)',
                'Form Tools for cropping images',
                'Form Type and tools for cropping images',
                null,
            ))
                ->setDocsLink('https://github.com/fengyuanchen/cropperjs', 'Cropper.js documentation.'),

            new UxPackage(
                'lazy-image',
                'Lazy Image',
                'app_lazy_image',
                '#AC2777',
                'linear-gradient(136deg, #AC2777 -8%, #F246AD 105%)',
                'Delay Loading with Blurhash',
                'Optimize Image Loading with BlurHash',
            ),

            new UxPackage(
                'dropzone',
                'Stylized Dropzone',
                'app_dropzone',
                '#AC9F27',
                'linear-gradient(135deg, #AC9F27 -8%, #E8D210 105%)',
                'Upload Files with Style',
                'Form type for stylized "drop zone" for file uploads',
                'I need an upload field that looks great'
            ),

            (new UxPackage(
                'swup',
                'Swup Integration',
                'app_swup',
                '#D87036',
                'linear-gradient(95deg, #D87036 -5%, #EA9633 105%)',
                'Stylized Page Transitions',
                'Integration with the page transition library Swup',
            ))
                ->setDocsLink('https://swup.js.org/', 'Swup documentation'),

            new UxPackage(
                'notify',
                'Notify',
                'app_notify',
                '#204CA0',
                'linear-gradient(95deg, #204CA0 -6%, #3D82EA 105%)',
                'Native Browser Notifications',
                'Trigger native browser notifications from inside PHP',
            ),

            new UxPackage(
                'toggle-password',
                'Toggle Password',
                'app_toggle_password',
                '#BE0404',
                'linear-gradient(142deg, #FD963C -15%, #BE0404 95%)',
                'Password Visibility Switch',
                'Switch the visibility of a password field',
            ),

            (new UxPackage(
                'typed',
                'Typed',
                'app_typed',
                '#20A091',
                'linear-gradient(95deg, #20A091 -5%, #4EC9B3 105%)',
                'Animated Typing with Typed.js',
                'Animated typing with Typed.js',
            ))
                ->setDocsLink('https://github.com/mattboldt/typed.js/', 'Typed.js documentation'),
        ];

        if (!$query) {
            return $packages;
        }

        return array_filter($packages, function (UxPackage $package) use ($query) {
            return str_contains($package->getName(), $query) || str_contains($package->getHumanName(), $query);
        });
    }

    public function find(string $name): UxPackage
    {
        $packages = $this->findAll();
        foreach ($packages as $package) {
            if ($package->getName() === $name) {
                return $package;
            }
        }

        throw new \InvalidArgumentException(\sprintf('Unknown package "%s".', $name));
    }

    public function count(): int
    {
        return \count($this->findAll());
    }

    public function findByRoute(string $route): UxPackage
    {
        $packages = $this->findAll();
        foreach ($packages as $package) {
            if ($package->getRoute() === $route) {
                return $package;
            }
        }

        throw new \InvalidArgumentException(\sprintf('Could not find a package for the current route "%s".', $route));
    }
}
