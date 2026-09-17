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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Spatie\Snapshots\Drivers\HtmlDriver;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\KitContextRunner;
use Symfony\UX\Toolkit\Kit\KitFactory;
use Symfony\UX\Toolkit\Kit\KitSynchronizer;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Recipe\RecipeSynchronizer;
use Symfony\UX\Toolkit\Recipe\RecipeType;
use Symfony\UX\Toolkit\Registry\LocalRegistry;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;

class ComponentsRenderingTest extends WebTestCase
{
    use MatchesSnapshots;
    use TestHelperTrait;

    private const KITS_DIR = __DIR__.'/../../kits';

    /**
     * @return iterable<string, string, string>
     */
    public static function provideTestComponentRendering(): iterable
    {
        $filesystem = new Filesystem();
        $kitSynchronizer = new KitSynchronizer($filesystem, new RecipeSynchronizer());

        foreach (LocalRegistry::getAvailableKitsName() as $kitName) {
            $kit = self::createLocalKit($kitName);
            $kitSynchronizer->synchronize($kit);

            foreach ($kit->getRecipes(RecipeType::Component) as $recipe) {
                foreach ($recipe->getExamples() as $i => $example) {
                    yield \sprintf('Kit %s, component %s, example #%d', $kitName, $recipe->name, $i) => [$kitName, $recipe->name, $example['code']];
                }
            }
        }
    }

    #[DataProvider('provideTestComponentRendering')]
    #[Group('skip-on-lowest')]
    public function testComponentRendering(string $kitName, string $recipeName, string $code): void
    {
        $twig = self::getContainer()->get('twig');
        /** @var KitContextRunner $kitContextRunner */
        $kitContextRunner = self::getContainer()->get('ux_toolkit.kit.kit_context_runner');

        $kit = $this->instantiateKit($kitName);
        $template = $twig->createTemplate($code);
        $renderedCode = $kitContextRunner->runForKit($kit, static fn () => $template->render());

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
                - Rendered code (run "php vendor/bin/phpunit -d --update-snapshots" to update snapshots): -->
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
                // Parsed and serialized by lexbor instead of libxml, whose output varies with the
                // version the contributor happens to have installed: attribute quoting differs
                // (libxml >= 2.15 writes &quot; inside double quotes where older releases switch to
                // single quotes), and libxml < 2.14 assumes Latin-1 when no charset is declared,
                // corrupting non-ASCII text such as the RTL examples.
                $document = \Dom\HTMLDocument::createFromString($data, \LIBXML_NOERROR | \LIBXML_HTML_NOIMPLIED);

                return $this->info."\n".trim($document->saveHtml());
            }
        });
    }
}
