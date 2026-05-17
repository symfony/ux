<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\GrapesJS\Preset;

use Symfony\UX\Editor\Bridge\GrapesJS\Config\GrapesJSConfig;
use Symfony\UX\Editor\Config\CommonOptions;
use Symfony\UX\Editor\Config\EditorConfigInterface;
use Symfony\UX\Editor\Config\Preset\EditorPresetInterface;

final class PageBuilderLandingPreset implements EditorPresetInterface
{
    public function build(): EditorConfigInterface
    {
        return new GrapesJSConfig(
            common: new CommonOptions(language: 'en'),
            blocks: [
                ['id' => 'hero', 'label' => 'Hero', 'category' => 'Layout', 'content' => '<section class="hero"><h1>Hero title</h1><p>Hero subtitle</p></section>'],
                ['id' => 'section', 'label' => 'Section', 'category' => 'Layout', 'content' => '<section><div class="container"><h2>Section</h2></div></section>'],
                ['id' => 'text', 'label' => 'Text', 'category' => 'Basic', 'content' => '<p>Insert your text here.</p>'],
                ['id' => 'image', 'label' => 'Image', 'category' => 'Basic', 'content' => '<img src="" alt="">'],
            ],
            deviceManager: [
                'devices' => [
                    ['name' => 'Desktop', 'width' => ''],
                    ['name' => 'Tablet', 'width' => '768px', 'widthMedia' => '992px'],
                    ['name' => 'Mobile', 'width' => '320px', 'widthMedia' => '480px'],
                ],
            ],
        );
    }
}
