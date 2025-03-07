<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Component;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;
use Twig\Extra\Html\HtmlExtension;

/**
 * @author Jean-François Lépine
 */
class BadgeTest extends KernelTestCase
{
    public function testDefaultRenderingIsPossible(): void
    {
        $this->bootKernel();

        $html = <<<EOT
<h1>Demo</h1>
<twig:Badge>my badge</twig:Badge>
EOT;
        /** @var Environment $twig */
        $twig = static::getContainer()->get('twig');
        $twig->addExtension(new HtmlExtension());

        $template = $twig->createTemplate($html);
        $output = $template->render([]);

        $this->assertStringContainsString('class="inline-flex items-center ', $output);
        $this->assertStringContainsString('my badge', $output);
    }
}
