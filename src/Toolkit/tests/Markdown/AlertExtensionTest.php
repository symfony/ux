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
use Symfony\UX\Toolkit\Markdown\Extension\Alert\AlertExtension;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader;

class AlertExtensionTest extends TestCase
{
    public function testRendersNoteAlert()
    {
        $converter = $this->createConverter();

        $html = (string) $converter->convert(<<<'MARKDOWN'
            > [!NOTE]
            > This is a note.
            MARKDOWN);

        $this->assertSame(
            "<div class=\"toolkit-alert toolkit-alert--note\">\n    <p>This is a note.</p>\n</div>\n\n",
            $html
        );
    }

    public function testRendersWarningAlert()
    {
        $converter = $this->createConverter();

        $html = (string) $converter->convert(<<<'MARKDOWN'
            > [!WARNING]
            > This is a warning.
            MARKDOWN);

        $this->assertSame(
            "<div class=\"toolkit-alert toolkit-alert--warning\">\n    <p>This is a warning.</p>\n</div>\n\n",
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
        $environment->addExtension(new AlertExtension($twig));

        return new MarkdownConverter($environment);
    }
}
