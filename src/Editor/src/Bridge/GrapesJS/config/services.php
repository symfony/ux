<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\UX\Editor\Bridge\GrapesJS\GrapesJSBridge;
use Symfony\UX\Editor\Bridge\GrapesJS\Preset\PageBuilderLandingPreset;

return static function (ContainerConfigurator $c): void {
    $s = $c->services()->defaults()->autowire()->autoconfigure();

    $s->set(GrapesJSBridge::class)->tag('ux.editor.bridge');
    $s->set(PageBuilderLandingPreset::class)->tag('ux.editor.preset', ['name' => 'page_builder.landing']);
};
