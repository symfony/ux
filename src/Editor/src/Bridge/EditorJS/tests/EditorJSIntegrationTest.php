<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\EditorJS\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Forms;
use Symfony\UX\Editor\Bridge\BridgeRegistry;
use Symfony\UX\Editor\Bridge\EditorJS\Config\EditorJSConfig;
use Symfony\UX\Editor\Bridge\EditorJS\Config\ToolDefinition;
use Symfony\UX\Editor\Bridge\EditorJS\EditorJSBridge;
use Symfony\UX\Editor\Config\Preset\PresetRegistry;
use Symfony\UX\Editor\Content\BlockContent;
use Symfony\UX\Editor\Form\EditorType;

final class EditorJSIntegrationTest extends TestCase
{
    public function testRoundTripThroughEditorType(): void
    {
        $factory = Forms::createFormFactoryBuilder()
            ->addType(new EditorType(new BridgeRegistry([new EditorJSBridge()]), new PresetRegistry([])))
            ->getFormFactory();

        $form = $factory->createBuilder()->add('body', EditorType::class, [
            'config' => new EditorJSConfig(tools: ['paragraph' => new ToolDefinition('Paragraph')]),
        ])->getForm();

        $payload = json_encode(['version' => '2.30.0', 'blocks' => [['type' => 'paragraph', 'data' => ['text' => 'hi']]]], \JSON_THROW_ON_ERROR);
        $form->submit(['body' => $payload]);
        self::assertTrue($form->isSynchronized());

        $data = $form->get('body')->getData();
        self::assertInstanceOf(BlockContent::class, $data);
        self::assertSame('editorjs', $data->getMetadata()['bridgeId']);
        self::assertSame('paragraph', $data->blocks[0]['type']);

        $view = $form->get('body')->createView();
        self::assertSame('symfony--ux-editor--editorjs', $view->vars['ux_editor']['controller']);
        $native = json_decode($view->vars['ux_editor']['wrapper_attr']['data-symfony--ux-editor--editorjs-config-value'], true);
        self::assertSame('Paragraph', $native['tools']['paragraph']['class']);
    }
}
