<?php

namespace App\Service;

use App\Model\UxPackage;

class UxPackageRepository
{
    /**
     * @return array<UxPackage>
     */
    public function findAll(string $query = null): array
    {
        $packages = [
            (new UxPackage(
                'turbo',
                'Turbo',
                'app_turbo',
                '#5920A0',
                'linear-gradient(95.22deg, #5920A0 -4.7%, #844EC9 105.43%)',
                'Integration with Turbo for single-page-app and real-time experience',
                'I need to transform my app into an SPA!'
            ))
                ->setDocsLink('https://turbo.hotwired.dev/handbook/introduction', 'Documentation specifically for the Turbo JavaScript library.')
                ->setScreencastLink('https://symfonycasts.com/screencast/turbo', 'Go deep into all 3 parts of Turbo.'),

            new UxPackage(
                'live-component',
                'Live Components',
                'app_live_component',
                '#AC5727',
                'linear-gradient(94.81deg, #AC5727 -1.76%, #CB804A 104.47%)',
                'Build dynamic interfaces with zero JavaScript',
                'I need Twig templates that update in real-time!'
            ),

            new UxPackage(
                'autocomplete',
                'Autocomplete',
                'app_autocomplete',
                '#DF275E',
                'linear-gradient(95.22deg, #DF275E -4.7%, #E85995 105.43%)',
                'Ajax-powered, auto-completable `select` elements',
                'I need an Ajax-autocomplete select field'
            ),

            (new UxPackage(
                'chartjs',
                'Chart.js',
                'app_chartjs',
                '#21A81E',
                'linear-gradient(95.26deg, #21A81E 0.06%, #45DD42 108.28%)',
                'Easy charts with Chart.js',
                'I need to build a chart'
            ))
                ->setDocsLink('https://www.chartjs.org/', 'Chart.js documentation.'),

            (new UxPackage(
                'react',
                'React',
                'app_react',
                '#10A2CB',
                'linear-gradient(95.22deg, #10a2cb -4.7%, #42caf0 105.43%)',
                'Quickly render `<React />` components &amp; pass them props.',
                'I need to render React components from Twig'
            ))
                ->setDocsLink('https://reactjs.org/', 'Go deeper with the React docs.'),

            (new UxPackage(
                'vue',
                'Vue.js',
                'app_vue',
                '#35b67c',
                'linear-gradient(95.22deg, #35B67C -4.7%, #8CE3BC 105.43%)',
                'Quickly render `<Vue />` components &amp; pass them props.',
                'I need to render Vue.js components from Twig'
            ))
                ->setDocsLink('https://vuejs.org/', 'Go deeper with the Vue.js docs.'),

            (new UxPackage(
                'svelte',
                'Svelte',
                'app_svelte',
                '#FF3E00',
                'linear-gradient(137.59deg, #FF3E00 -7.89%, #FF8A00 110.57%)',
                'Quickly render `<Svelte />` components &amp; pass them props.',
                'I need to render Svelte components from Twig',
                'svelte.svg',
            ))
                ->setDocsLink('https://svelte.dev/', 'Go deeper with the Svelte docs.'),

            (new UxPackage(
                'cropperjs',
                'Image Cropper',
                'app_cropperjs',
                '#1E8FA8',
                'linear-gradient(135.73deg, #1E8FA8 -7.05%, #3FC0DC 105.11%)',
                'Form Type and tools for cropping images',
                'I need to add a JavaScript image cropper'
            ))
                ->setDocsLink('https://github.com/fengyuanchen/cropperjs', 'Cropper.js documentation.'),

            new UxPackage(
                'lazy-image',
                'Lazy Image',
                'app_lazy_image',
                '#AC2777',
                'linear-gradient(133.55deg, #AC2777 -8.06%, #F246AD 104.87%)',
                'Optimize Image Loading with BlurHash',
                'I need to delay large image loading'
            ),

            new UxPackage(
                'twig-component',
                'Twig Components',
                'app_twig_component',
                '#7FA020',
                'linear-gradient(95.22deg, #7FA020 -4.7%, #A1C94E 105.43%)',
                'Create PHP classes that can render themselves',
                'I need to create PHP classes that render'
            ),

            new UxPackage(
                'dropzone',
                'Stylized Dropzone',
                'app_dropzone',
                '#AC9F27',
                'linear-gradient(135.69deg, #AC9F27 -8.56%, #E8D210 106.51%)',
                'Form type for stylized "drop zone" for file uploads',
                'I need an upload field that looks great'
            ),
            (new UxPackage(
                'swup',
                'Swup Integration',
                'app_swup',
                '#D87036',
                'linear-gradient(95.22deg, #D87036 -4.7%, #EA9633 105.43%)',
                'Integration with the page transition library Swup',
                'I need stylized page transitions'
            ))
                ->setDocsLink('https://swup.js.org/', 'Swup documentation'),

            new UxPackage(
                'notify',
                'Notify',
                'app_notify',
                '#204CA0',
                'linear-gradient(94.17deg, #204CA0 -6.1%, #3D82EA 105.25%)',
                'Trigger native browser notifications from inside PHP',
                'I need to send browser notifications',
            ),

            new UxPackage(
                'toggle-password',
                'Toggle Password',
                'app_toggle_password',
                '#BE0404',
                'linear-gradient(142.8deg, #FD963C -14.8%, #BE0404 95.43%)',
                'Switch the visibility of a password field',
                'I need to toggle the visibility of a password field',
            ),

            (new UxPackage(
                'typed',
                'Typed',
                'app_typed',
                '#20A091',
                'linear-gradient(95.22deg, #20A091 -4.7%, #4EC9B3 105.43%)',
                'Animated typing with Typed.js',
                'I need to type onto the screen... like this'
            ))
                ->setDocsLink('https://github.com/mattboldt/typed.js/', 'Typed.js documentation'),

            new UxPackage(
                'translator',
                'Translator',
                'app_translator',
                '#2248D0',
                'linear-gradient(139.1deg, #2248D0 -20.18%, #00FFB2 113.25%)',
                "Use Symfony's translations in JavaScript",
                'I need to translate strings in JavaScript',
                'translator.svg'
            ),

            // new UxPackage('form-collection', 'Form Collection', 'app_form_collection', 'linear-gradient(95.22deg, #5920A0 -4.7%, #844EC9 105.43%), #5920A0', 'Handle CollectionType embedded forms with zero custom JavaScript'),
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

        throw new \InvalidArgumentException(sprintf('Unknown package "%s"', $name));
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

        throw new \InvalidArgumentException(sprintf('Could not find a package for the current route "%s"', $route));
    }
}
