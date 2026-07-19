<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Doc;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Doc\RecipeDocRenderer;
use Symfony\UX\Toolkit\Kit\KitFactory;
use Symfony\UX\Toolkit\Registry\LocalRegistry;

/**
 * Renders every recipe that ships a `README.md`, guarding the migrated docs: `renderAsMarkdown()` reads
 * every file a `::: example` directive points at (throwing if one is missing) and expands the
 * `::: installation` / `::: api-reference` directives.
 */
final class AllRecipesDocRenderTest extends KernelTestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideDocumentedRecipes(): iterable
    {
        self::bootKernel();

        $kitFactory = self::getContainer()->get('ux_toolkit.kit.kit_factory');
        \assert($kitFactory instanceof KitFactory);

        foreach (LocalRegistry::getAvailableKitsName() as $kitName) {
            $kit = $kitFactory->createKitFromAbsolutePath(Path::join(__DIR__, '../../kits', $kitName));
            foreach ($kit->getRecipes() as $recipe) {
                if (null !== $recipe->doc) {
                    yield "$kitName/$recipe->name" => [$kitName, $recipe->name];
                }
            }
        }

        self::ensureKernelShutdown();
    }

    #[DataProvider('provideDocumentedRecipes')]
    public function testDocMdRenders(string $kitName, string $recipeName)
    {
        $kitFactory = self::getContainer()->get('ux_toolkit.kit.kit_factory');
        \assert($kitFactory instanceof KitFactory);

        $kit = $kitFactory->createKitFromAbsolutePath(Path::join(__DIR__, '../../kits', $kitName));
        $recipe = $kit->getRecipe($recipeName);
        $this->assertNotNull($recipe);

        $markdown = new RecipeDocRenderer(self::getContainer()->get('twig'))->renderAsMarkdown($kit, $recipe);

        // Every directive was expanded: no unresolved `::: ...` line survives.
        $this->assertDoesNotMatchRegularExpression('/^::: /m', $markdown, 'An unresolved directive is left in the rendered doc.');
        $this->assertStringContainsString('## Installation', $markdown);
        // A literal apostrophe leaking as `&#039;` means a field was Twig-escaped into the portable Markdown.
        $this->assertStringNotContainsString('&#039;', $markdown, 'An HTML entity leaked into the portable Markdown.');
    }
}
