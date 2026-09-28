<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Kit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Kit\KitFactory;
use Symfony\UX\Toolkit\Kit\KitSynchronizer;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Recipe\RecipeSynchronizer;
use Symfony\UX\Toolkit\Registry\LocalRegistry;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;

final class KitPreviewAssetsTest extends TestCase
{
    use TestHelperTrait;

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideKitStylesheets(): iterable
    {
        foreach (LocalRegistry::getAvailableKitsName() as $kitName) {
            $kitCss = Path::join(self::getLocalKitPath($kitName), 'kit.css');
            if (is_file($kitCss)) {
                yield $kitName => [$kitCss];
            }
        }
    }

    #[DataProvider('provideKitStylesheets')]
    public function testKitStylesheetOnlyHoldsTheTheme(string $kitCss): void
    {
        $content = file_get_contents($kitCss);

        $this->assertStringNotContainsString('@import', $content);
        $this->assertStringNotContainsString('@source', $content);
    }

    /**
     * @return iterable<string, array{Recipe}>
     */
    public static function provideRecipesShippingControllers(): iterable
    {
        $filesystem = new Filesystem();
        $kitFactory = new KitFactory($filesystem, new KitSynchronizer($filesystem, new RecipeSynchronizer()));

        foreach (LocalRegistry::getAvailableKitsName() as $kitName) {
            $kit = $kitFactory->createKitFromAbsolutePath(self::getLocalKitPath($kitName));
            foreach ($kit->getRecipes() as $recipe) {
                if (is_dir(Path::join($recipe->absolutePath, 'assets/controllers'))) {
                    yield $kitName.'/'.$recipe->name => [$recipe];
                }
            }
        }
    }

    #[DataProvider('provideRecipesShippingControllers')]
    public function testRecipeInstallsTheControllersItShips(Recipe $recipe): void
    {
        $installedFiles = array_map(static fn ($file) => $file->sourceRelativePathName, $recipe->getFiles());

        foreach (glob(Path::join($recipe->absolutePath, 'assets/controllers/*_controller.js')) as $controller) {
            $this->assertContains(Path::makeRelative($controller, $recipe->absolutePath), $installedFiles);
        }
    }

    public function testTailwindKitsShipAStylesheet(): void
    {
        $kitsWithStylesheet = array_keys(iterator_to_array(self::provideKitStylesheets()));

        $this->assertSame(['common', 'flowbite-4', 'shadcn'], $kitsWithStylesheet);
    }
}
