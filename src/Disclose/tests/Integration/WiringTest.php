<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Disclose\Context\DiscloseContext;
use Symfony\UX\Disclose\Tests\Fixtures\Subject\FixtureData;

/**
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class WiringTest extends KernelTestCase
{
    public function testComponentRendersBothViewsWithoutExposingTheValue(): void
    {
        static::bootKernel();
        $twig = static::getContainer()->get('twig');

        $html = $twig->render('disclose.html.twig', [
            'context' => DiscloseContext::create(FixtureData::class, '42', 'secret'),
        ]);

        self::assertStringContainsString('data-controller="disclose"', $html);
        self::assertStringContainsString('data-disclose-target="button"', $html);
        self::assertStringContainsString('data-disclose-target="content"', $html);
        self::assertStringContainsString('data-disclose-url-value="', $html);
        self::assertStringNotContainsString('the-secret', $html);
    }

    public function testInlineRevealBlockSwitchesTheTriggerToHtmlModeWithoutExposingTheValue(): void
    {
        static::bootKernel();
        $twig = static::getContainer()->get('twig');

        $html = $twig->render('disclose_reveal_block.html.twig', [
            'context' => DiscloseContext::create(FixtureData::class, '42'),
        ]);

        self::assertStringContainsString('data-disclose-render-html-value="true"', $html);
        self::assertStringContainsString('data-disclose-url-value="', $html);
        self::assertStringNotContainsString('the-secret', $html);
    }

    public function testGeneratedUrlPointsToTheBundleRouteWithASignedContext(): void
    {
        static::bootKernel();
        $url = static::getContainer()
            ->get('ux.disclose.url_generator')
            ->generate(DiscloseContext::create(FixtureData::class, '42', 'secret'))
        ;

        self::assertStringStartsWith('/test/disclose/ux/disclose', $url);
        self::assertStringContainsString('d=', $url);
        self::assertStringContainsString('h=', $url);
    }
}
