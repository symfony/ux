<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Installer;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Toolkit\Installer\ComponentNameRewriter;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;

final class ComponentNameRewriterTest extends KernelTestCase
{
    use TestHelperTrait;

    public function testPrefixesTheComponentsOfTheKit(): void
    {
        $rewriter = new ComponentNameRewriter(['Button', 'Dialog', 'Dialog:Trigger', 'Card:Header'], 'ui:');

        $this->assertSame(<<<'TWIG'
            <twig:ui:Dialog id="dialog">
                <twig:ui:Dialog:Trigger>
                    <twig:ui:Button variant="outline">Open</twig:ui:Button>
                </twig:ui:Dialog:Trigger>
                <twig:ui:Card:Header/>
                {{ component('ui:Button', {variant: 'ghost'}) }}
                {{ component("ui:Dialog:Trigger") }}
            </twig:ui:Dialog>
            TWIG, $rewriter->rewrite(<<<'TWIG'
            <twig:Dialog id="dialog">
                <twig:Dialog:Trigger>
                    <twig:Button variant="outline">Open</twig:Button>
                </twig:Dialog:Trigger>
                <twig:Card:Header/>
                {{ component('Button', {variant: 'ghost'}) }}
                {{ component("Dialog:Trigger") }}
            </twig:Dialog>
            TWIG));
    }

    public function testPrefixesTagsSpanningSeveralLines(): void
    {
        $rewriter = new ComponentNameRewriter(['Button'], 'ui:forms:');

        $this->assertSame(<<<'TWIG'
            <twig:ui:forms:Button
                variant="ghost"
            />
            TWIG, $rewriter->rewrite(<<<'TWIG'
            <twig:Button
                variant="ghost"
            />
            TWIG));
    }

    public function testLeavesOtherComponentsUntouched(): void
    {
        $rewriter = new ComponentNameRewriter(['Button', 'Dialog'], 'ui:');

        $contents = <<<'TWIG'
            <twig:ux:icon name="lucide:x" />
            <twig:ButtonGroup><twig:Dialog:Footer /></twig:ButtonGroup>
            {{ component('App:Alert') }}
            <dialog data-slot="dialog">Button</dialog>
            TWIG;

        $this->assertSame($contents, $rewriter->rewrite($contents));
    }

    public function testPrefixesTheComponentsTheKitInstallsThroughThePlaceholder(): void
    {
        $kitFactory = self::getContainer()->get('ux_toolkit.kit.kit_factory');
        $kit = $kitFactory->createKitFromAbsolutePath(self::getFixtureKitPath('with-installation-placeholders'));
        $contents = '<twig:Dialog><twig:Dialog:Content /></twig:Dialog> <twig:Notice />';

        $this->assertSame(
            '<twig:ui:Dialog><twig:ui:Dialog:Content /></twig:ui:Dialog> <twig:Notice />',
            ComponentNameRewriter::forKit($kit, 'ui:')->rewrite($contents),
        );
        $this->assertSame($contents, ComponentNameRewriter::forKit($kit, '')->rewrite($contents));
    }

    public function testDoesNothingWithoutPrefix(): void
    {
        $contents = '<twig:Button>Click</twig:Button>';

        $this->assertSame($contents, new ComponentNameRewriter(['Button'], '')->rewrite($contents));
    }
}
