<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Form;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Forms;
use Symfony\UX\Editor\Bridge\AbstractBridge;
use Symfony\UX\Editor\Bridge\BridgeRegistry;
use Symfony\UX\Editor\Config\AbstractEditorConfig;
use Symfony\UX\Editor\Config\BridgeCapabilities;
use Symfony\UX\Editor\Config\CommonOptions;
use Symfony\UX\Editor\Config\EditorConfigInterface;
use Symfony\UX\Editor\Config\Preset\PresetRegistry;
use Symfony\UX\Editor\Content\EditorContentInterface;
use Symfony\UX\Editor\Content\HtmlContent;
use Symfony\UX\Editor\Exception\IncompatibleConfigException;
use Symfony\UX\Editor\Form\DataTransformer\EditorContentTransformerInterface;
use Symfony\UX\Editor\Form\DataTransformer\StorageShape;
use Symfony\UX\Editor\Form\EditorType;

final class EditorTypeStrictTest extends TestCase
{
    public function testStrictCapabilitiesThrowsOnIncompatibleToolbar()
    {
        $factory = Forms::createFormFactoryBuilder()
            ->addType(new EditorType(new BridgeRegistry([new NoToolbarBridge()]), new PresetRegistry([])))
            ->getFormFactory();
        $form = $factory->createBuilder()->add('body', EditorType::class, [
            'config' => new NoToolbarConfig(new CommonOptions(toolbar: ['bold'])),
            'strictCapabilities' => true,
        ])->getForm();

        $this->expectException(IncompatibleConfigException::class);
        // createView invokes buildView which calls $config->toNative() -> assertCapabilities() (strict mode -> throws).
        $form->get('body')->createView();
    }
}

final class NoToolbarConfig extends AbstractEditorConfig
{
    public function getBridgeId(): string
    {
        return 'notb';
    }

    public function getCapabilities(): BridgeCapabilities
    {
        return new BridgeCapabilities(false, true, true, true, ['html']);
    }

    protected function translateCommon(CommonOptions $c): array
    {
        return [];
    }
}

final class NoToolbarBridge extends AbstractBridge
{
    public function getId(): string
    {
        return 'notb';
    }

    public function getDefaultConfig(): EditorConfigInterface
    {
        return new NoToolbarConfig();
    }

    public function getCapabilities(): BridgeCapabilities
    {
        return new NoToolbarConfig()->getCapabilities();
    }

    public function createTransformer(): EditorContentTransformerInterface
    {
        return new class implements EditorContentTransformerInterface {
            public function getBridgeId(): string
            {
                return 'notb';
            }

            public function getContentClass(): string
            {
                return HtmlContent::class;
            }

            public function getStorageShape(): StorageShape
            {
                return StorageShape::Scalar;
            }

            public function transform(?EditorContentInterface $c): mixed
            {
                return $c?->getRaw();
            }

            public function reverseTransform(mixed $v): ?EditorContentInterface
            {
                return null === $v ? null : new HtmlContent((string) $v);
            }
        };
    }
}
