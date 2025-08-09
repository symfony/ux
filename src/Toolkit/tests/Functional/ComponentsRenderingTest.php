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
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\KitContextRunner;
use Symfony\UX\Toolkit\Kit\KitFactory;
use Symfony\UX\Toolkit\Recipe\Recipe;
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
            $kitDir = Path::join(__DIR__, '../../kits', $kitName);
            $docsFinder = (new Finder())->files()->name('EXAMPLES.md')->in($kitDir)->depth(1);

            foreach ($docsFinder as $docFile) {
                $componentName = $docFile->getRelativePath();

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
    public function testComponentRendering(string $kitName, string $recipeName, string $code)
    {
        $twig = self::getContainer()->get('twig');
        /** @var KitContextRunner $kitContextRunner */
        $kitContextRunner = self::getContainer()->get('ux_toolkit.kit.kit_context_runner');

        $kit = $this->instantiateKit($kitName);
        $template = $twig->createTemplate($code);
        $renderedCode = $kitContextRunner->runForKit($kit, fn () => $template->render());

        $this->assertCodeRenderedMatchesHtmlSnapshot($kit, $kit->getRecipe($recipeName), $code, $renderedCode);
    }

    private function instantiateKit(string $kitName): Kit
    {
        $kitFactory = self::getContainer()->get('ux_toolkit.kit.kit_factory');

        self::assertInstanceOf(KitFactory::class, $kitFactory);

        return $kitFactory->createKitFromAbsolutePath(Path::join(__DIR__, '../../kits', $kitName));
    }

    private function assertCodeRenderedMatchesHtmlSnapshot(Kit $kit, Recipe $recipe, string $code, string $renderedCode): void
    {
        $info = \sprintf(
            <<<HTML
            <!--
            - Kit: %s
            - Component: %s
            - Code:
            ```twig
            %s
            ```
            - Rendered code (prettified for testing purposes, run "php vendor/bin/phpunit -d --update-snapshots" to update snapshots): -->
            HTML,
            $kit->manifest->name,
            $recipe->manifest->name,
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
