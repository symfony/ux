<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Bridge\Format\Wysiwyg;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Forms;
use Symfony\UX\Editor\Bridge\BridgeRegistry;
use Symfony\UX\Editor\Bridge\Format\Wysiwyg\AbstractWysiwygBridge;
use Symfony\UX\Editor\Bridge\Format\Wysiwyg\AbstractWysiwygConfig;
use Symfony\UX\Editor\Bridge\Format\Wysiwyg\AbstractWysiwygTransformer;
use Symfony\UX\Editor\Config\CommonOptions;
use Symfony\UX\Editor\Config\EditorConfigInterface;
use Symfony\UX\Editor\Config\Preset\PresetRegistry;
use Symfony\UX\Editor\Content\HtmlContent;
use Symfony\UX\Editor\Form\DataTransformer\EditorContentTransformerInterface;
use Symfony\UX\Editor\Form\EditorType;

final class WysiwygIntegrationTest extends TestCase
{
    public function testFullRoundTrip(): void
    {
        $factory = Forms::createFormFactoryBuilder()
            ->addType(new EditorType(new BridgeRegistry([new FakeWysiwygBridge()]), new PresetRegistry([])))
            ->getFormFactory();
        $form = $factory->createBuilder()->add('body', EditorType::class, [
            'config' => new FakeWysiwygConfig(new CommonOptions(placeholder: 'Write…')),
        ])->getForm();

        $form->submit(['body' => '<p>hello</p>']);
        self::assertTrue($form->isSynchronized());
        $data = $form->get('body')->getData();
        self::assertInstanceOf(HtmlContent::class, $data);
        self::assertSame('<p>hello</p>', $data->html);
        self::assertSame('fakewy', $data->getMetadata()['bridgeId']);

        $view = $form->get('body')->createView();
        self::assertSame('symfony--ux-editor--fakewy', $view->vars['ux_editor']['controller']);
        $native = json_decode($view->vars['ux_editor']['wrapper_attr']['data-symfony--ux-editor--fakewy-config-value'], true);
        self::assertSame('Write…', $native['placeholder']);
    }
}

final class FakeWysiwygConfig extends AbstractWysiwygConfig
{
    public function getBridgeId(): string { return 'fakewy'; }
}

final class FakeWysiwygBridge extends AbstractWysiwygBridge
{
    public function getId(): string { return 'fakewy'; }
    public function getDefaultConfig(): EditorConfigInterface { return new FakeWysiwygConfig(); }
    public function createTransformer(): EditorContentTransformerInterface
    {
        return new class extends AbstractWysiwygTransformer {
            public function getBridgeId(): string { return 'fakewy'; }
        };
    }
}
