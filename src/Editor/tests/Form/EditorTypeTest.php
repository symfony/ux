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
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
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

final class EditorTypeTest extends TestCase
{
    public function testTypedConfigModeRoundTrips(): void
    {
        $form = $this->factory()->createBuilder()
            ->add('body', EditorType::class, ['config' => new EditorTypeTestFakeConfig()])
            ->getForm();
        $form->submit(['body' => '<p>hi</p>']);
        self::assertTrue($form->isSynchronized());
        $data = $form->get('body')->getData();
        self::assertInstanceOf(HtmlContent::class, $data);
        self::assertSame('<p>hi</p>', $data->html);
    }

    public function testBridgePlusArrayMode(): void
    {
        $form = $this->factory()->createBuilder()
            ->add('body', EditorType::class, ['bridge' => 'fake', 'common' => ['placeholder' => 'x']])
            ->getForm();
        $form->submit(['body' => 'plain']);
        self::assertSame('plain', $form->get('body')->getData()->html);
    }

    public function testSanitizeOnSubmitWhenEnabled(): void
    {
        $sanitizer = new HtmlSanitizer(new HtmlSanitizerConfig()->allowSafeElements());
        $form = $this->factory($sanitizer)->createBuilder()
            ->add('body', EditorType::class, ['config' => new EditorTypeTestFakeConfig(), 'sanitize' => true])
            ->getForm();
        $form->submit(['body' => '<script>x</script><p>ok</p>']);
        self::assertStringNotContainsString('<script>', $form->get('body')->getData()->html);
    }

    public function testSanitizeOffWhenDisabled(): void
    {
        $sanitizer = new HtmlSanitizer(new HtmlSanitizerConfig()->allowSafeElements());
        $form = $this->factory($sanitizer)->createBuilder()
            ->add('body', EditorType::class, ['config' => new EditorTypeTestFakeConfig(), 'sanitize' => false])
            ->getForm();
        $form->submit(['body' => '<script>x</script>']);
        self::assertSame('<script>x</script>', $form->get('body')->getData()->html);
    }

    private function factory(?HtmlSanitizer $sanitizer = null): \Symfony\Component\Form\FormFactoryInterface
    {
        $bridges = new BridgeRegistry([new EditorTypeTestFakeBridge()]);
        $presets = new PresetRegistry([]);

        return Forms::createFormFactoryBuilder()
            ->addType(new EditorType($bridges, $presets, $sanitizer))
            ->getFormFactory();
    }
}

final class EditorTypeTestFakeConfig extends AbstractEditorConfig
{
    public function getBridgeId(): string
    {
        return 'fake';
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

final class EditorTypeTestFakeBridge extends AbstractBridge
{
    public function getId(): string
    {
        return 'fake';
    }

    public function getDefaultConfig(): EditorConfigInterface
    {
        return new EditorTypeTestFakeConfig();
    }

    public function getCapabilities(): BridgeCapabilities
    {
        return new EditorTypeTestFakeConfig()->getCapabilities();
    }

    public function createTransformer(): EditorContentTransformerInterface
    {
        return new class implements EditorContentTransformerInterface {
            public function getBridgeId(): string
            {
                return 'fake';
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
