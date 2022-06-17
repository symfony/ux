<?php

namespace App\Service;

use App\Model\Package;

class PackageRepository
{
    /**
     * @return array<Package>
     */
    public function findAll(string $query = null): array
    {
        $packages = [
            (new Package(
                'turbo',
                'Turbo',
                'app_turbo',
                'linear-gradient(95.22deg, #5920A0 -4.7%, #844EC9 105.43%), #5920A0',
                'Integration with Turbo for single-page-app and real-time experience',
                'I need to transform my app into an SPA!'
            ))
                ->setDocsLink('https://turbo.hotwired.dev/handbook/introduction', 'Documentation specifically for the Turbo JavaScript library.')
                ->setScreencastLink('https://symfonycasts.com/screencast/turbo', 'Go deep into all 3 parts of Turbo.'),

            new Package(
                'live-component',
                'Live Components',
                'app_live_component',
                'linear-gradient(94.81deg, #AC5727 -1.76%, #CB804A 104.47%)',
                'Build dynamic interfaces with zero JavaScript',
                'I need Twig templates that update in real-time!'
            ),

            new Package(
                'autocomplete',
                'Autocomplete',
                'app_autocomplete',
                'linear-gradient(95.22deg, #DF275E -4.7%, #E85995 105.43%), #5920A0',
                'Ajax-powered, auto-completable `select` elements',
                'I need an Ajax-autocomplete select field'
            ),
            (new Package(
                'chartjs',
                'Chart.js',
                'app_chartjs',
                'linear-gradient(95.26deg, #21A81E 0.06%, #45DD42 108.28%)',
                'Easy charts with Chart.js',
                'I need to build a chart'
            ))
                ->setDocsLink('https://www.chartjs.org/', 'Chart.js documentation.'),

            (new Package(
                'react',
                'React',
                'app_react',
                'linear-gradient(95.22deg, #35D07C -4.7%, #58D7C7 105.43%), #5920A0',
                'Quickly render `<React />` components &amp; pass them props.',
                'I need to render React components from Twig'
            ))
                ->setDocsLink('https://reactjs.org/', 'Go deeper with the React docs.'),

            (new Package(
                'cropperjs',
                'Image Cropper',
                'app_cropper',
                'linear-gradient(135.73deg, #1E8FA8 -7.05%, #3FC0DC 105.11%)',
                'Form Type and tools for cropping images',
                'I need to add a JavaScript image cropper'
            ))
                ->setDocsLink('https://github.com/fengyuanchen/cropperjs', 'Cropper.js documentation.'),

            new Package(
                'lazy-image',
                'Lazy Image',
                'app_lazy_image',
                'linear-gradient(133.55deg, #AC2777 -8.06%, #F246AD 104.87%)',
                'Optimize Image Loading with BlurHash',
                'I need to delay large image loading'
            ),

            new Package(
                'twig-component',
                'Twig Components',
                'app_twig_component',
                'linear-gradient(95.22deg, #7FA020 -4.7%, #A1C94E 105.43%), #5920A0',
                'Create PHP classes that can render themselves',
                'I need to create PHP classes that render'
            ),

            new Package(
                'dropzone',
                'Stylized Dropzone',
                'app_dropzone',
                'linear-gradient(135.69deg, #AC9F27 -8.56%, #E8D210 106.51%)',
                'Form type for stylized "drop zone" for file uploads',
                'I need an upload field that looks great'
            ),
            (new Package(
                'swup',
                'Swup Integration',
                'app_swup',
                'linear-gradient(95.22deg, #D87036 -4.7%, #EA9633 105.43%), #5920A0',
                'Integration with the page transition library Swup',
                'I need stylized page transitions'
            ))
                ->setDocsLink('https://swup.js.org/', 'Swup documentation'),

            new Package('notify',
                'Notify',
                'app_notify',
                'linear-gradient(94.17deg, #204CA0 -6.1%, #3D82EA 105.25%)',
                'Trigger native browser notifications from inside PHP',
                'I need to send browser notifications',
            ),
            (new Package(
                'typed',
                'Typed',
                'app_typed',
                'linear-gradient(95.22deg, #20A091 -4.7%, #4EC9B3 105.43%), #5920A0',
                'Animated typing with Typed.js',
                'I need to type onto the screen... like this'
            ))
                ->setDocsLink('https://github.com/mattboldt/typed.js/', 'Typed.js documentation'),
            // new Package('form-collection', 'Form Collection', 'app_form_collection', 'linear-gradient(95.22deg, #5920A0 -4.7%, #844EC9 105.43%), #5920A0', 'Handle CollectionType embedded forms with zero custom JavaScript'),
        ];

        if (!$query) {
            return $packages;
        }

        return array_filter($packages, function (Package $package) use ($query) {
            return str_contains($package->getName(), $query) || str_contains($package->getHumanName(), $query);
        });
    }

    public function find(string $name): Package
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
}
