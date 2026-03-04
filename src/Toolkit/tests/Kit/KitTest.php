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

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\KitManifest;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Recipe\RecipeManifest;
use Symfony\UX\Toolkit\Recipe\RecipeType;

final class KitTest extends TestCase
{
    public function testShouldFailIfKitNameIsInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid kit name "-foobar".');

        new Kit(__DIR__, new KitManifest('-foobar', 'Description', 'MIT', 'https://example.com'));
    }

    public function testShouldFailIfKitPathIsNotAbsolute()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Kit path "./%s" is not absolute.', __DIR__));

        new Kit(\sprintf('./%s', __DIR__), new KitManifest('foo', 'Description', 'MIT', 'https://example.com'));
    }

    public function testCanAddRecipesToTheKit()
    {
        $kit = new Kit(__DIR__, new KitManifest('foo', 'Description', 'MIT', 'https://example.com'));
        $kit->addRecipe(new Recipe(
            'alert',
            __DIR__.'/alert',
            new RecipeManifest(RecipeType::Component, 'Alert', 'Description', []),
        ));
        $kit->addRecipe(new Recipe(
            'table',
            __DIR__.'/table',
            new RecipeManifest(RecipeType::Component, 'Table', 'Description', []),
        ));
        $kit->addRecipe(new Recipe(
            'login',
            __DIR__.'/Login',
            new RecipeManifest(RecipeType::Block, 'Login', 'Description', []),
        ));

        $this->assertCount(3, $kit->getRecipes());
        $this->assertCount(2, $kit->getRecipes(type: RecipeType::Component));
        $this->assertCount(1, $kit->getRecipes(type: RecipeType::Block));
    }

    public function testShouldFailIfComponentIsAlreadyRegisteredInTheKit()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Recipe "Alert" is already registered in the kit.');

        $kit = new Kit(__DIR__, new KitManifest('foo', 'Description', 'MIT', 'https://example.com'));
        $kit->addRecipe(new Recipe(
            'alert',
            __DIR__.'/alert',
            new RecipeManifest(RecipeType::Component, 'Alert', 'Description', []),
        ));
        $kit->addRecipe(new Recipe(
            'alert',
            __DIR__.'/alert',
            new RecipeManifest(RecipeType::Component, 'Alert', 'Description', []),
        ));
    }

    public function testCanGetRecipeByName()
    {
        $kit = new Kit(__DIR__, new KitManifest('foo', 'Description', 'MIT', 'https://example.com'));
        $kit->addRecipe(new Recipe(
            'alert',
            __DIR__.'/Alert',
            new RecipeManifest(RecipeType::Component, 'Alert', 'Description', []),
        ));
        $kit->addRecipe(new Recipe(
            'table',
            __DIR__.'/Table',
            new RecipeManifest(RecipeType::Component, 'Table', 'Description', []),
        ));

        $this->assertSame('Table', $kit->getRecipe('table')->manifest->name);
        $this->assertSame('Alert', $kit->getRecipe('alert')->manifest->name);
    }

    public function testShouldReturnNullIfRecipeIsNotFound()
    {
        $kit = new Kit(__DIR__, new KitManifest('foo', 'Description', 'MIT', 'https://example.com'));

        $this->assertNull($kit->getRecipe('table'));
    }
}
