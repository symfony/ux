<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Bridge\Format\Block;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Forms;
use Symfony\UX\Editor\Bridge\BridgeRegistry;
use Symfony\UX\Editor\Bridge\Format\Block\AbstractBlockBridge;
use Symfony\UX\Editor\Bridge\Format\Block\AbstractBlockConfig;
use Symfony\UX\Editor\Bridge\Format\Block\AbstractBlockTransformer;
use Symfony\UX\Editor\Config\EditorConfigInterface;
use Symfony\UX\Editor\Config\Preset\PresetRegistry;
use Symfony\UX\Editor\Content\BlockContent;
use Symfony\UX\Editor\Form\DataTransformer\EditorContentTransformerInterface;
use Symfony\UX\Editor\Form\EditorType;

final class BlockIntegrationTest extends TestCase
{
    public function testJsonRoundTripThroughEditorType(): void
    {
        $factory = Forms::createFormFactoryBuilder()
            ->addType(new EditorType(new BridgeRegistry([new FakeBlockBridge()]), new PresetRegistry([])))
            ->getFormFactory();
        $form = $factory->createBuilder()->add('body', EditorType::class, ['config' => new FakeBlockConfig()])->getForm();
        $payload = json_encode(['version' => '1.0', 'blocks' => [['type' => 'paragraph', 'data' => ['text' => 'hi']]]], \JSON_THROW_ON_ERROR);
        $form->submit(['body' => $payload]);
        self::assertTrue($form->isSynchronized());
        $data = $form->get('body')->getData();
        self::assertInstanceOf(BlockContent::class, $data);
        self::assertSame('paragraph', $data->blocks[0]['type']);
    }
}

final class FakeBlockConfig extends AbstractBlockConfig
{
    public function getBridgeId(): string { return 'fakeblock'; }
}

final class FakeBlockBridge extends AbstractBlockBridge
{
    public function getId(): string { return 'fakeblock'; }
    public function getDefaultConfig(): EditorConfigInterface { return new FakeBlockConfig(); }
    public function createTransformer(): EditorContentTransformerInterface
    {
        return new class extends AbstractBlockTransformer {
            public function getBridgeId(): string { return 'fakeblock'; }
        };
    }
}
