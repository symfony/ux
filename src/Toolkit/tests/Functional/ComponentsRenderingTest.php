<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Functional;

use Spatie\Snapshots\Drivers\HtmlDriver;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\UX\Toolkit\Asset\Component;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\KitFactory;
use Symfony\UX\Toolkit\Registry\LocalRegistry;

class ComponentsRenderingTest extends WebTestCase
{
    use MatchesSnapshots;

    private const KITS_DIR = __DIR__.'/../../kits';

    /**
     * @return iterable<string, string, string>
     */
    public static function provideTestComponentRendering(): iterable
    {
        foreach (LocalRegistry::getAvailableKitsName() as $kitName) {
            $kitDir = Path::join(__DIR__, '../../kits', $kitName, 'docs/components');
            $docsFinder = (new Finder())->files()->name('*.md')->in($kitDir)->depth(0);

            foreach ($docsFinder as $docFile) {
                $componentName = $docFile->getFilenameWithoutExtension();

                $codeBlockMatchesResult = preg_match_all('/```twig.*?\n(?P<code>.+?)```/s', $docFile->getContents(), $codeBlockMatches);
                if (false === $codeBlockMatchesResult || 0 === $codeBlockMatchesResult) {
                    throw new \RuntimeException(\sprintf('No Twig code blocks found in file "%s"', $docFile->getRelativePathname()));
                }

                foreach ($codeBlockMatches['code'] as $i => $code) {
                    yield \sprintf('Kit %s, component %s, code #%d', $kitName, $componentName, $i + 1) => [$kitName, $componentName, $code];
                }
            }
        }
    }

    /**
     * @dataProvider provideTestComponentRendering
     */
    public function testComponentRendering(string $kitName, string $componentName, string $code): void
    {
        $twig = self::getContainer()->get('twig');
        $kitContextRunner = self::getContainer()->get('ux_toolkit.kit.kit_context_runner');

        $kit = $this->instantiateKit($kitName);
        $template = $twig->createTemplate($code);
        $renderedCode = $kitContextRunner->runForKit($kit, fn () => $template->render());

        $this->assertCodeRenderedMatchesHtmlSnapshot($kit, $kit->getComponent($componentName), $code, $renderedCode);
    }

    private function instantiateKit(string $kitName): Kit
    {
        $kitFactory = self::getContainer()->get('ux_toolkit.kit.kit_factory');

        self::assertInstanceOf(KitFactory::class, $kitFactory);

        return $kitFactory->createKitFromAbsolutePath(Path::join(__DIR__, '../../kits', $kitName));
    }

    private function assertCodeRenderedMatchesHtmlSnapshot(Kit $kit, Component $component, string $code, string $renderedCode): void
    {
        $info = \sprintf(<<<HTML
            <!--
            - Kit: %s
            - Component: %s
            - Code:
            ```twig
            %s
            ```
            - Rendered code (prettified for testing purposes, run "php vendor/bin/phpunit -d --update-snapshots" to update snapshots): -->
            HTML,
            $kit->name,
            $component->name,
            trim($code)
        );

        $this->assertMatchesSnapshot($renderedCode, new class($info) extends HtmlDriver {
            public function __construct(private string $info)
            {
            }

            public function serialize($data): string
            {
                $serialized = parent::serialize($data);
                $serialized = str_replace(['<html><body>', '</body></html>'], '', $serialized);
                $serialized = trim($serialized);

                return $this->info."\n".$serialized;
            }
        });
    }
}
