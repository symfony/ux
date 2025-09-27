<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Recipe;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\KitManifest;
use Symfony\UX\Toolkit\Recipe\RecipeSynchronizer;

final class RecipeSynchronizerTest extends TestCase
{
    public function testSynchronize()
    {
        $kit = new Kit(__DIR__, new KitManifest('foo', 'Description', 'MIT', 'https://example.com'));
        $recipeSynchronizer = new RecipeSynchronizer();

        $this->assertEmpty($kit->getRecipes());

        $recipeSynchronizer->synchronizeRecipe($kit, new SplFileInfo(__DIR__.'/../../kits/shadcn/alert/manifest.json', 'alert', 'alert/manifest.json'));

        $recipeAlert = $kit->getRecipe('alert');
        $this->assertNotNull($recipeAlert);
        $this->assertEquals('Alert', $recipeAlert->manifest->name);
        $this->assertEquals('A notification component that displays important messages with an icon, title, and description.', $recipeAlert->manifest->description);
    }
}
