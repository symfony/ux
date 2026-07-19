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

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Markdown\Extension\Popover\PopoverExtension;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader;

class PopoverExtensionTest extends TestCase
{
    public function testRendersPopover()
    {
        $converter = $this->createConverter();

        $html = (string) $converter->convert('The prop value (?)[Some **bold** description].');

        $this->assertSame(
            '<p>The prop value <span class="toolkit-popover" data-controller="toolkit-popover" data-toolkit-popover-id="prop-description-1">'
            .'<button type="button" class="toolkit-popover__trigger" aria-describedby="prop-description-1" data-action="toolkit-popover#toggle">?</button>'
            .'<span class="toolkit-popover__content" id="prop-description-1" role="tooltip" data-toolkit-popover-target="content">Some <strong>bold</strong> description</span>'
            ."</span>.</p>\n",
            $html
        );
    }

    private function createConverter(): MarkdownConverter
    {
        $loader = new FilesystemLoader();
        $loader->addPath(\dirname(__DIR__, 2).'/templates', 'UXToolkit');

        $twig = new TwigEnvironment($loader);

        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new PopoverExtension($twig));

        return new MarkdownConverter($environment);
    }
}
