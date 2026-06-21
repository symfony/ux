<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\CKEditor\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Forms;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\UX\Editor\Bridge\BridgeRegistry;
use Symfony\UX\Editor\Bridge\CKEditor\CKEditorBridge;
use Symfony\UX\Editor\Bridge\CKEditor\Config\CKEditorConfig;
use Symfony\UX\Editor\Config\CommonOptions;
use Symfony\UX\Editor\Config\Preset\PresetRegistry;
use Symfony\UX\Editor\Content\HtmlContent;
use Symfony\UX\Editor\Form\EditorType;

final class CKEditorIntegrationTest extends TestCase
{
    public function testRoundTripThroughEditorType()
    {
        $factory = Forms::createFormFactoryBuilder()
            ->addType(new EditorType(new BridgeRegistry([new CKEditorBridge()]), new PresetRegistry([])))
            ->getFormFactory();

        $form = $factory->createBuilder()->add('body', EditorType::class, [
            'config' => new CKEditorConfig(common: new CommonOptions(toolbar: ['bold', 'italic'])),
        ])->getForm();

        $form->submit(['body' => '<p>hello</p>']);
        self::assertTrue($form->isSynchronized());

        $data = $form->get('body')->getData();
        self::assertInstanceOf(HtmlContent::class, $data);
        self::assertSame('<p>hello</p>', $data->html);
        self::assertSame('ckeditor', $data->getMetadata()['bridgeId']);

        $view = $form->get('body')->createView();
        self::assertSame('symfony--ux-editor--ckeditor', $view->vars['ux_editor']['controller']);
        $native = json_decode($view->vars['ux_editor']['wrapper_attr']['data-symfony--ux-editor--ckeditor-config-value'], true);
        self::assertSame(['items' => ['bold', 'italic']], $native['toolbar']);
    }

    public function testSanitizeStripsScriptOnSubmit()
    {
        $sanitizer = new HtmlSanitizer(new HtmlSanitizerConfig()->allowSafeElements());
        $factory = Forms::createFormFactoryBuilder()
            ->addType(new EditorType(new BridgeRegistry([new CKEditorBridge()]), new PresetRegistry([]), $sanitizer))
            ->getFormFactory();

        $form = $factory->createBuilder()->add('body', EditorType::class, [
            'config' => new CKEditorConfig(),
            'sanitize' => true,
        ])->getForm();
        $form->submit(['body' => '<script>alert(1)</script><p>ok</p>']);
        self::assertStringNotContainsString('<script>', $form->get('body')->getData()->html);
        self::assertStringContainsString('<p>ok</p>', $form->get('body')->getData()->html);
    }
}
