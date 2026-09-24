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

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Installer\ComponentNameRewriter;

final class ComponentNameRewriterTest extends TestCase
{
    private const COMPONENT_NAMES = [
        'Button' => true,
        'Dialog' => true,
        'Dialog:Content' => true,
        'Dialog:Title' => true,
    ];

    public function testShouldPrefixKnownComponents(): void
    {
        $rewriter = new ComponentNameRewriter(self::COMPONENT_NAMES, 'ui:');

        $this->assertSame('<twig:ui:Button variant="ghost" />', $rewriter->rewrite('<twig:Button variant="ghost" />'));
    }

    public function testShouldPrefixNestedComponentsAndClosingTags(): void
    {
        $rewriter = new ComponentNameRewriter(self::COMPONENT_NAMES, 'ui:');

        $this->assertSame(
            "<twig:ui:Dialog>\n    <twig:ui:Dialog:Content>Hello</twig:ui:Dialog:Content>\n</twig:ui:Dialog>",
            $rewriter->rewrite("<twig:Dialog>\n    <twig:Dialog:Content>Hello</twig:Dialog:Content>\n</twig:Dialog>"),
        );
    }

    public function testShouldNotTouchComponentsFromOtherPackages(): void
    {
        $rewriter = new ComponentNameRewriter(self::COMPONENT_NAMES, 'ui:');

        $source = "<twig:block name=\"content\">\n    <twig:ux:icon name=\"lucide:x\" />\n</twig:block>";

        $this->assertSame($source, $rewriter->rewrite($source));
    }

    public function testShouldNotTouchUnknownComponents(): void
    {
        $rewriter = new ComponentNameRewriter(self::COMPONENT_NAMES, 'ui:');

        $this->assertSame('<twig:MyOwnCard />', $rewriter->rewrite('<twig:MyOwnCard />'));
    }

    public function testShouldNotTouchAnUnknownSubComponentOfAKnownComponent(): void
    {
        $rewriter = new ComponentNameRewriter(self::COMPONENT_NAMES, 'ui:');

        $this->assertSame('<twig:Dialog:Unknown />', $rewriter->rewrite('<twig:Dialog:Unknown />'));
    }

    public function testShouldDoNothingWithoutAPrefix(): void
    {
        $rewriter = new ComponentNameRewriter(self::COMPONENT_NAMES, '');

        $this->assertSame('<twig:Button />', $rewriter->rewrite('<twig:Button />'));
    }

    public function testShouldDoNothingWithoutAnyComponentName(): void
    {
        $rewriter = new ComponentNameRewriter([], 'ui:');

        $this->assertSame('<twig:Button />', $rewriter->rewrite('<twig:Button />'));
    }
}
