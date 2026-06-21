<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Bridge\Format\Page;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\Format\Page\PageSandboxRenderer;
use Symfony\UX\Editor\Content\PageContent;

final class PageSandboxRendererTest extends TestCase
{
    public function testSandboxedIframe()
    {
        $out = new PageSandboxRenderer()->render(new PageContent('<h1>X</h1>', 'h1{color:red}'));
        self::assertStringContainsString('<iframe', $out);
        self::assertStringContainsString('sandbox="allow-same-origin"', $out);
        self::assertStringContainsString('srcdoc=', $out);
        self::assertStringContainsString('&lt;h1&gt;X&lt;/h1&gt;', $out);
        self::assertStringContainsString('h1{color:red}', $out);
    }

    public function testCustomSandbox()
    {
        $out = new PageSandboxRenderer('allow-same-origin allow-scripts')->render(new PageContent('<p>x</p>'));
        self::assertStringContainsString('sandbox="allow-same-origin allow-scripts"', $out);
    }

    public function testEmptyPageProducesEmptyString()
    {
        self::assertSame('', new PageSandboxRenderer()->render(new PageContent('')));
    }
}
