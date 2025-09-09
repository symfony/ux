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

namespace Symfony\UX\Toolkit\Tests\Installer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Toolkit\Dependency\ImportmapPackageDependency;
use Symfony\UX\Toolkit\Dependency\NpmPackageDependency;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Dependency\RecipeDependency;
use Symfony\UX\Toolkit\Dependency\Version;
use Symfony\UX\Toolkit\Installer\PoolResolver;
use Symfony\UX\Toolkit\Kit\KitSynchronizer;
use Symfony\UX\Toolkit\Recipe\RecipeSynchronizer;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;

final class PoolResolverTest extends TestCase
{
    use TestHelperTrait;

    public function testCanResolveDependencies()
    {
        $kitSynchronizer = new KitSynchronizer(new Filesystem(), new RecipeSynchronizer());
        $kit = self::createLocalKit('shadcn');
        $kitSynchronizer->synchronize($kit);

        $poolResolver = new PoolResolver();

        $pool = $poolResolver->resolveForRecipe($kit, $recipeButton = $kit->getRecipe('Button'));

        $this->assertEquals([
            'templates/components/Button.html.twig',
        ], array_keys($pool->getFiles()[$recipeButton->absolutePath]));
        $this->assertCount(3, $pool->getPhpPackageDependencies());

        $pool = $poolResolver->resolveForRecipe($kit, $recipeTable = $kit->getRecipe('Table'));

        $this->assertEquals([
            'templates/components/Table.html.twig',
            'templates/components/Table/Body.html.twig',
            'templates/components/Table/Caption.html.twig',
            'templates/components/Table/Cell.html.twig',
            'templates/components/Table/Footer.html.twig',
            'templates/components/Table/Head.html.twig',
            'templates/components/Table/Header.html.twig',
            'templates/components/Table/Row.html.twig',
        ], array_keys($pool->getFiles()[$recipeTable->absolutePath]));
        $this->assertCount(1, $pool->getPhpPackageDependencies());
    }

    public function testCanHandleCircularRecipeDependencies()
    {
        $kitSynchronizer = new KitSynchronizer(new Filesystem(), new RecipeSynchronizer());
        $kit = self::createFixtureKit('with-circular-components-dependencies');
        $kitSynchronizer->synchronize($kit);

        $poolResolver = new PoolResolver();

        $recipeA = $kit->getRecipe('A');
        $recipeB = $kit->getRecipe('B');
        $recipeC = $kit->getRecipe('C');

        $this->assertEquals([new RecipeDependency('B')], $recipeA->manifest->dependencies);
        $this->assertEquals([new RecipeDependency('C')], $recipeB->manifest->dependencies);
        $this->assertEquals([new RecipeDependency('A')], $recipeC->manifest->dependencies);

        $pool = $poolResolver->resolveForRecipe($kit, $recipeA);

        $this->assertCount(3, $pool->getFiles());
        $this->assertEquals(['templates/components/A.html.twig'], array_keys($pool->getFiles()[$recipeA->absolutePath]));
        $this->assertEquals(['templates/components/B.html.twig'], array_keys($pool->getFiles()[$recipeB->absolutePath]));
        $this->assertEquals(['templates/components/C.html.twig'], array_keys($pool->getFiles()[$recipeC->absolutePath]));
        $this->assertCount(0, $pool->getPhpPackageDependencies());
    }

    public function testCanHandleAllPossibleDependencies()
    {
        $kitSynchronizer = new KitSynchronizer(new Filesystem(), new RecipeSynchronizer());
        $kit = self::createFixtureKit('with-many-dependencies');
        $kitSynchronizer->synchronize($kit);

        $poolResolver = new PoolResolver();

        $recipeAlert = $kit->getRecipe('Alert');
        $recipeButton = $kit->getRecipe('Button');

        $this->assertEquals([
            new PhpPackageDependency('twig/html-extra', new Version('^3.12.0')),
            new PhpPackageDependency('tales-from-a-dev/twig-tailwind-extra'),
            new NpmPackageDependency('tailwindcss', new Version('^4.0.0')),
            new ImportmapPackageDependency('@hotwired/stimulus'),
            new RecipeDependency('Button'),
        ], $recipeAlert->manifest->dependencies);

        $this->assertEquals([
            new PhpPackageDependency('twig/html-extra', new Version('^3.12.0')),
            new PhpPackageDependency('another/php-package', new Version('^2.0')),
            new NpmPackageDependency('another-npm-package', new Version('^1.0.0')),
            new ImportmapPackageDependency('another-importmap-package'),
        ], $recipeButton->manifest->dependencies);

        $pool = $poolResolver->resolveForRecipe($kit, $recipeAlert);

        $this->assertCount(0, $pool->getFiles());

        $this->assertEquals([
            'twig/html-extra' => new PhpPackageDependency('twig/html-extra', new Version('^3.12.0')),
            'tales-from-a-dev/twig-tailwind-extra' => new PhpPackageDependency('tales-from-a-dev/twig-tailwind-extra'),
            'another/php-package' => new PhpPackageDependency('another/php-package', new Version('^2.0')),
        ], $pool->getPhpPackageDependencies());

        $this->assertEquals([
            'tailwindcss' => new NpmPackageDependency('tailwindcss', new Version('^4.0.0')),
            'another-npm-package' => new NpmPackageDependency('another-npm-package', new Version('^1.0.0')),
        ], $pool->getNpmPackageDependencies());

        $this->assertEquals([
            '@hotwired/stimulus' => new ImportmapPackageDependency('@hotwired/stimulus'),
            'another-importmap-package' => new ImportmapPackageDependency('another-importmap-package'),
        ], $pool->getImportmapPackageDependencies());
    }
}
