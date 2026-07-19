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
use Symfony\UX\Toolkit\Markdown\Extension\Tabs\TabsExtension;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader;

class TabsExtensionTest extends TestCase
{
    public function testRendersTwoTabs()
    {
        $converter = $this->createConverter();

        $html = (string) $converter->convert(<<<'MARKDOWN'
            ::: tabs
            :: tab PHP
            This is the PHP tab.
            :: tab JavaScript
            This is the JavaScript tab.
            :::
            MARKDOWN);

        $this->assertSame(
            \sprintf(
                "<div class=\"toolkit-tabs\">\n"
                ."    <div class=\"toolkit-tabs__list\" role=\"tablist\">\n"
                ."                <button type=\"button\" class=\"toolkit-tab\" role=\"tab\" aria-selected=\"true\" data-tab-id=\"%1\$s\">PHP</button>\n"
                ."                <button type=\"button\" class=\"toolkit-tab\" role=\"tab\" aria-selected=\"false\" data-tab-id=\"%2\$s\">JavaScript</button>\n"
                ."            </div>\n"
                ."        <div class=\"toolkit-tabs__panel\" role=\"tabpanel\" data-tab-id=\"%1\$s\">\n"
                ."        <p>This is the PHP tab.</p>\n"
                ."    </div>\n"
                ."        <div class=\"toolkit-tabs__panel\" role=\"tabpanel\" data-tab-id=\"%2\$s\" hidden>\n"
                ."        <p>This is the JavaScript tab.</p>\n"
                ."    </div>\n"
                ."    </div>\n\n",
                hash('xxh3', 'PHP'),
                hash('xxh3', 'JavaScript'),
            ),
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
        $environment->addExtension(new TabsExtension($twig));

        return new MarkdownConverter($environment);
    }
}
