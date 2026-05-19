<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\GrapesJS\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Forms;
use Symfony\UX\Editor\Bridge\BridgeRegistry;
use Symfony\UX\Editor\Bridge\GrapesJS\Config\GrapesJSConfig;
use Symfony\UX\Editor\Bridge\GrapesJS\GrapesJSBridge;
use Symfony\UX\Editor\Config\Preset\PresetRegistry;
use Symfony\UX\Editor\Content\PageContent;
use Symfony\UX\Editor\Form\EditorType;

final class GrapesJSIntegrationTest extends TestCase
{
    public function testRoundTripThroughEditorType(): void
    {
        $factory = Forms::createFormFactoryBuilder()
            ->addType(new EditorType(new BridgeRegistry([new GrapesJSBridge()]), new PresetRegistry([])))
            ->getFormFactory();

        $form = $factory->createBuilder()->add('body', EditorType::class, [
            'config' => new GrapesJSConfig(canvasCss: 'body{margin:0}'),
        ])->getForm();

        $payload = json_encode([
            'html' => '<h1>X</h1>',
            'css' => 'h1{color:red}',
            'assets' => [['type' => 'image', 'src' => '/a.png']],
            'components' => [['type' => 'h1']],
        ], \JSON_THROW_ON_ERROR);
        $form->submit(['body' => $payload]);
        self::assertTrue($form->isSynchronized());

        $data = $form->get('body')->getData();
        self::assertInstanceOf(PageContent::class, $data);
        self::assertSame('grapesjs', $data->getMetadata()['bridgeId']);
        self::assertSame('<h1>X</h1>', $data->html);
        self::assertSame('h1{color:red}', $data->css);
        self::assertCount(1, $data->assets);
        self::assertCount(1, $data->components);

        $view = $form->get('body')->createView();
        self::assertSame('symfony--ux-editor--grapesjs', $view->vars['ux_editor']['controller']);
        $native = json_decode($view->vars['ux_editor']['wrapper_attr']['data-symfony--ux-editor--grapesjs-config-value'], true);
        self::assertSame('body{margin:0}', $native['canvas']['styles'][0]);
    }
}
