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
use Symfony\UX\Toolkit\File;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Recipe\RecipeManifest;
use Symfony\UX\Toolkit\Recipe\RecipeType;

final class RecipeTest extends TestCase
{
    public function testShouldFailWhenPathIsNotAbsolute()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kit path "relative/path" is not absolute.');

        new Recipe('test-recipe', 'relative/path', new RecipeManifest(
            type: RecipeType::Component,
            name: 'Test Recipe',
            description: 'A test recipe',
            copyFiles: [],
        ));
    }

    public function testShouldFailWhenInvalidCopyFiles()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Copy file destination "/" must be a relative path.');

        new Recipe('test-recipe', __DIR__.'/../../kits/shadcn/Table', new RecipeManifest(
            type: RecipeType::Component,
            name: 'Test Recipe',
            description: 'A test recipe',
            copyFiles: [
                'templates/' => '/',
            ],
        ));
    }

    public function testGetCopyFiles()
    {
        $recipe = new Recipe('test-recipe', __DIR__.'/../../kits/shadcn/table', new RecipeManifest(
            type: RecipeType::Component,
            name: 'Test Recipe',
            description: 'A test recipe',
            copyFiles: [
                'templates/' => 'templates/',
            ],
        ));

        $this->assertEquals([
            new File('templates/components/Table.html.twig', 'templates/components/Table.html.twig'),
            new File('templates/components/Table/Body.html.twig', 'templates/components/Table/Body.html.twig'),
            new File('templates/components/Table/Caption.html.twig', 'templates/components/Table/Caption.html.twig'),
            new File('templates/components/Table/Cell.html.twig', 'templates/components/Table/Cell.html.twig'),
            new File('templates/components/Table/Footer.html.twig', 'templates/components/Table/Footer.html.twig'),
            new File('templates/components/Table/Head.html.twig', 'templates/components/Table/Head.html.twig'),
            new File('templates/components/Table/Header.html.twig', 'templates/components/Table/Header.html.twig'),
            new File('templates/components/Table/Row.html.twig', 'templates/components/Table/Row.html.twig'),
        ], iterator_to_array($recipe->getFiles()));
    }

    public function testGetCopyFilesWithDifferentDestDir()
    {
        $recipe = new Recipe('test-recipe', __DIR__.'/../../kits/shadcn/table', new RecipeManifest(
            type: RecipeType::Component,
            name: 'Test Recipe',
            description: 'A test recipe',
            copyFiles: [
                'templates/' => 'dest-templates/',
            ],
        ));

        $this->assertEquals([
            new File('templates/components/Table.html.twig', 'dest-templates/components/Table.html.twig'),
            new File('templates/components/Table/Body.html.twig', 'dest-templates/components/Table/Body.html.twig'),
            new File('templates/components/Table/Caption.html.twig', 'dest-templates/components/Table/Caption.html.twig'),
            new File('templates/components/Table/Cell.html.twig', 'dest-templates/components/Table/Cell.html.twig'),
            new File('templates/components/Table/Footer.html.twig', 'dest-templates/components/Table/Footer.html.twig'),
            new File('templates/components/Table/Head.html.twig', 'dest-templates/components/Table/Head.html.twig'),
            new File('templates/components/Table/Header.html.twig', 'dest-templates/components/Table/Header.html.twig'),
            new File('templates/components/Table/Row.html.twig', 'dest-templates/components/Table/Row.html.twig'),
        ], iterator_to_array($recipe->getFiles()));
    }
}
