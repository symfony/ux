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
use Symfony\UX\Editor\Bridge\EditorJS\BlockRenderer\HeaderRenderer;
use Symfony\UX\Editor\Bridge\EditorJS\BlockRenderer\ImageRenderer;
use Symfony\UX\Editor\Bridge\EditorJS\BlockRenderer\ListRenderer;
use Symfony\UX\Editor\Bridge\EditorJS\BlockRenderer\ParagraphRenderer;
use Symfony\UX\Editor\Bridge\EditorJS\BlockRenderer\QuoteRenderer;
use Symfony\UX\Editor\Bridge\EditorJS\EditorJSBridge;
use Symfony\UX\Editor\Bridge\EditorJS\Preset\BlogStandardPreset;

return static function (ContainerConfigurator $c): void {
    $s = $c->services()->defaults()->autowire()->autoconfigure();

    $s->set(EditorJSBridge::class)->tag('ux.editor.bridge');
    $s->set(BlogStandardPreset::class)->tag('ux.editor.preset', ['name' => 'blog.standard']);

    foreach ([ParagraphRenderer::class, HeaderRenderer::class, ListRenderer::class, ImageRenderer::class, QuoteRenderer::class] as $r) {
        $s->set($r)->tag('ux.editor.block_renderer');
    }
};
