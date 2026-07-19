<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Markdown;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Markdown\CodeOptions;
use Symfony\UX\Toolkit\Markdown\PreviewTabsBuilder;

class PreviewTabsBuilderTest extends TestCase
{
    public function testCodeTabCarriesTheCollapseClassOptionForHostStyling()
    {
        // The code tab is styled by the host (ux.symfony.com collapses long class attributes), so that
        // option travels through the FencedCode info string; height is a Preview-tab concern and stays out.
        $tabs = PreviewTabsBuilder::build('<twig:Button />', new CodeOptions(height: '300px', collapseClass: true), 'twig');

        $this->assertSame('twig {"collapseClass":true}', $this->findFencedCode($tabs)?->getInfo());
    }

    public function testCodeTabHasBareLanguageWhenCollapseClassIsOff()
    {
        $tabs = PreviewTabsBuilder::build('<twig:Button />', new CodeOptions(height: '300px'), 'twig');

        $this->assertSame('twig', $this->findFencedCode($tabs)?->getInfo());
    }

    private function findFencedCode(Node $node): ?FencedCode
    {
        foreach ($node->iterator() as $child) {
            if ($child instanceof FencedCode) {
                return $child;
            }
        }

        return null;
    }
}
