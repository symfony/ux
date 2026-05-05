<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\UX\Editor\Bridge\BridgeInterface;
use Symfony\UX\Editor\Bridge\BridgeRegistry;
use Symfony\UX\Editor\Config\Preset\EditorPresetInterface;
use Symfony\UX\Editor\Config\Preset\PresetRegistry;
use Symfony\UX\Editor\Content\Converter\ContentConverterInterface;
use Symfony\UX\Editor\Content\Converter\ContentConverterRegistry;
use Symfony\UX\Editor\Form\EditorType;
use Symfony\UX\Editor\Upload\DefaultLocalUploadHandler;
use Symfony\UX\Editor\Upload\EditorUploadController;
use Symfony\UX\Editor\Upload\EditorUploadHandlerInterface;
use Symfony\UX\Editor\Upload\SignedUploadUrlGenerator;
use Symfony\UX\Editor\Upload\UploadHandlerRegistry;

return static function (ContainerConfigurator $c): void {
    $s = $c->services()->defaults()->autowire()->autoconfigure();

    $s->instanceof(BridgeInterface::class)->tag('ux.editor.bridge');
    $s->instanceof(EditorPresetInterface::class)->tag('ux.editor.preset');
    $s->instanceof(ContentConverterInterface::class)->tag('ux.editor.content_converter');
    $s->instanceof(EditorUploadHandlerInterface::class)->tag('ux.editor.upload_handler');

    $s->set(BridgeRegistry::class)->args([tagged_iterator('ux.editor.bridge')]);
    $s->set(PresetRegistry::class)->args([tagged_iterator('ux.editor.preset', indexAttribute: 'name')]);
    $s->set(ContentConverterRegistry::class)->args([tagged_iterator('ux.editor.content_converter')]);
    $s->set(UploadHandlerRegistry::class)->args([tagged_iterator('ux.editor.upload_handler', indexAttribute: 'profile')]);

    $s->set(SignedUploadUrlGenerator::class)->args(['%kernel.secret%', '%ux_editor.upload.ttl_seconds%']);
    $s->set(EditorUploadController::class)->public()->arg('$defaultProfile', '%ux_editor.upload.default_profile%');

    $s->set(EditorType::class)
        ->arg('$sanitizer', service('html_sanitizer.sanitizer.default')->nullOnInvalid());

    $s->set('symfony.ux_editor.upload_handler.default', DefaultLocalUploadHandler::class)
        ->args(['%kernel.project_dir%/public/uploads', '/uploads'])
        ->tag('ux.editor.upload_handler', ['profile' => 'default']);

    $s->set(\Symfony\UX\Editor\Command\DebugEditorCommand::class)->tag('console.command');

    $s->set(\Symfony\UX\Editor\DataCollector\UXEditorDataCollector::class)
        ->tag('data_collector', ['id' => 'ux_editor', 'template' => '@UXEditor/Collector/template.html.twig']);
};
