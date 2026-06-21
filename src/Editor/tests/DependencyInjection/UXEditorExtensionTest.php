<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\Editor\Bridge\BridgeRegistry;
use Symfony\UX\Editor\Config\Preset\PresetRegistry;
use Symfony\UX\Editor\Content\Converter\ContentConverterRegistry;
use Symfony\UX\Editor\DependencyInjection\UXEditorExtension;
use Symfony\UX\Editor\Form\EditorType;
use Symfony\UX\Editor\Upload\EditorUploadController;
use Symfony\UX\Editor\Upload\SignedUploadUrlGenerator;
use Symfony\UX\Editor\Upload\UploadHandlerRegistry;

final class UXEditorExtensionTest extends TestCase
{
    public function testServicesRegistered()
    {
        $c = new ContainerBuilder();
        $c->setParameter('kernel.debug', true);
        $c->setParameter('kernel.secret', 'test-secret');
        $c->setParameter('kernel.project_dir', sys_get_temp_dir());
        new UXEditorExtension()->load([], $c);

        foreach ([
            BridgeRegistry::class,
            PresetRegistry::class,
            ContentConverterRegistry::class,
            UploadHandlerRegistry::class,
            SignedUploadUrlGenerator::class,
            EditorUploadController::class,
            EditorType::class,
        ] as $id) {
            self::assertTrue($c->hasDefinition($id) || $c->hasAlias($id), "Missing service $id");
        }
    }
}
