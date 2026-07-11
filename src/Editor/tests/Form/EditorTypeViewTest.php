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
use Symfony\UX\Editor\Form\DataTransformer\EditorContentTransformerInterface;
use Symfony\UX\Editor\Form\DataTransformer\StorageShape;
use Symfony\UX\Editor\Form\EditorType;

final class EditorTypeViewTest extends TestCase
{
    public function testStimulusAttrsOnView()
    {
        $factory = Forms::createFormFactoryBuilder()
            ->addType(new EditorType(new BridgeRegistry([new EditorTypeViewTestFakeBridge()]), new PresetRegistry([])))
            ->getFormFactory();
        $form = $factory->createBuilder()->add('body', EditorType::class, ['config' => new EditorTypeViewTestFakeConfig()])->getForm();
        $view = $form->get('body')->createView();

        // Controller attrs live on vars.ux_editor.wrapper_attr (rendered on the wrapper div by the form theme).
        $editor = $view->vars['ux_editor'];
        self::assertSame('symfony--ux-editor--fake-view', $editor['controller']);
        self::assertSame('symfony--ux-editor--fake-view', $editor['wrapper_attr']['data-controller']);
        self::assertSame('html', $editor['wrapper_attr']['data-symfony--ux-editor--fake-view-format-value']);
        self::assertJson($editor['wrapper_attr']['data-symfony--ux-editor--fake-view-config-value']);

        // The textarea itself carries only the input-target attr so AbstractEditorController.inputTarget resolves.
        $attrs = $view->vars['attr'];
        self::assertSame('input', $attrs['data-symfony--ux-editor--fake-view-target']);
    }
}

final class EditorTypeViewTestFakeConfig extends AbstractEditorConfig
{
    public function getBridgeId(): string
    {
        return 'fake-view';
    }

    public function getCapabilities(): BridgeCapabilities
    {
        return new BridgeCapabilities(true, true, true, true, ['html']);
    }

    protected function translateCommon(CommonOptions $c): array
    {
        return [];
    }
}

final class EditorTypeViewTestFakeBridge extends AbstractBridge
{
    public function getId(): string
    {
        return 'fake-view';
    }

    public function getDefaultConfig(): EditorConfigInterface
    {
        return new EditorTypeViewTestFakeConfig();
    }

    public function getCapabilities(): BridgeCapabilities
    {
        return new EditorTypeViewTestFakeConfig()->getCapabilities();
    }

    public function createTransformer(): EditorContentTransformerInterface
    {
        return new class implements EditorContentTransformerInterface {
            public function getBridgeId(): string
            {
                return 'fake-view';
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
