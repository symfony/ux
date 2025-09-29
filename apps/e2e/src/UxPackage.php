<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App;

enum UxPackage: string
{
    case Autocomplete = 'UX Autocomplete';
    case ChartJs = 'UX Chart';
    case Cropperjs = 'UX Cropperjs';
    case Icons = 'UX Icons';
    //case LazyImage = 'UX LazyImage'; // deprecated/removed
    case Map = 'UX Map';
    case Notify = 'UX Notify';
    case React = 'UX React';
    case StimulusBundle = 'UX StimulusBundle';
    case Svelte = 'UX Svelte';
    // case Swup; // deprecated/removed
    // case TogglePassword; // deprecated/removed
    // case Toolkit; // not subject to E2E
    case Translator = 'UX Translator';
    case Turbo = 'UX Turbo';
    case TwigComponent = 'UX TwigComponent';
    // case Typed; // deprecated
    case Vue = 'UX Vue';

    public function getDocumentationUrl(): string
    {
        return match($this) {
            self::Autocomplete => 'https://ux.symfony.com/autocomplete',
            self::ChartJs => 'https://ux.symfony.com/chartjs',
            self::Cropperjs => 'https://ux.symfony.com/cropperjs',
            self::Icons => 'https://ux.symfony.com/icons',
            self::Map => 'https://ux.symfony.com/map',
            self::Notify => 'https://ux.symfony.com/notify',
            self::React => 'https://ux.symfony.com/react',
            self::StimulusBundle => 'https://ux.symfony.com/stimulus',
            self::Svelte => 'https://ux.symfony.com/svelte',
            self::Translator => 'https://ux.symfony.com/translator',
            self::Turbo => 'https://ux.symfony.com/turbo',
            self::TwigComponent => 'https://ux.symfony.com/twig-component',
            self::Vue => 'https://ux.symfony.com/vue',
        };
    }
}
