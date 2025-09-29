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
use Symfony\UX\Toolkit\Dependency\ConstraintVersion;
use Symfony\UX\Toolkit\Dependency\ImportmapPackageDependency;
use Symfony\UX\Toolkit\Dependency\NpmPackageDependency;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\File;
use Symfony\UX\Toolkit\Installer\Pool;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Recipe\RecipeManifest;
use Symfony\UX\Toolkit\Recipe\RecipeType;

final class PoolTest extends TestCase
{
    public function testCanAddFiles()
    {
        $pool = new Pool();

        $this->assertCount(0, $pool->getFiles());

        $recipe = new Recipe('test-recipe', __DIR__, new RecipeManifest(
            type: RecipeType::Component,
            name: 'Test Recipe',
            description: 'A test recipe',
            copyFiles: [],
        ));
        $pool->addFile($recipe, new File('path/to/file.html.twig', 'file.html.twig'));
        $pool->addFile($recipe, new File('path/to/another-file.html.twig', 'another-file.html.twig'));

        $this->assertCount(1, $pool->getFiles());
        $this->assertCount(2, $pool->getFiles()[$recipe->absolutePath]);
    }

    public function testCantAddSameFileTwice()
    {
        $pool = new Pool();

        $recipe = new Recipe('test-recipe', __DIR__, new RecipeManifest(
            type: RecipeType::Component,
            name: 'Test Recipe',
            description: 'A test recipe',
            copyFiles: [],
        ));
        $pool->addFile($recipe, new File('path/to/file.html.twig', 'file.html.twig'));
        $pool->addFile($recipe, new File('path/to/file.html.twig', 'file.html.twig'));

        $this->assertCount(1, $pool->getFiles());
    }

    public function testCanAddPhpPackageDependencies()
    {
        $pool = new Pool();

        $pool->addPhpPackageDependency(new PhpPackageDependency('twig/html-extra'));

        $this->assertCount(1, $pool->getPhpPackageDependencies());
    }

    public function testCantAddSamePhpPackageDependencyTwice()
    {
        $pool = new Pool();

        $pool->addPhpPackageDependency(new PhpPackageDependency('twig/html-extra'));
        $pool->addPhpPackageDependency(new PhpPackageDependency('twig/html-extra'));

        $this->assertCount(1, $pool->getPhpPackageDependencies());
    }

    public function testCanAddPhpPackageDependencyWithHigherVersion()
    {
        $pool = new Pool();

        $pool->addPhpPackageDependency(new PhpPackageDependency('twig/html-extra', new ConstraintVersion('^3.11.0')));

        $this->assertCount(1, $pool->getPhpPackageDependencies());
        $this->assertEquals('twig/html-extra:^3.11.0', (string) $pool->getPhpPackageDependencies()['twig/html-extra']);

        $pool->addPhpPackageDependency(new PhpPackageDependency('twig/html-extra', new ConstraintVersion('^3.12.0')));

        $this->assertCount(1, $pool->getPhpPackageDependencies());
        $this->assertEquals('twig/html-extra:^3.12.0', (string) $pool->getPhpPackageDependencies()['twig/html-extra']);

        $pool->addPhpPackageDependency(new PhpPackageDependency('twig/html-extra', new ConstraintVersion('^3.11.0')));

        $this->assertCount(1, $pool->getPhpPackageDependencies());
        $this->assertEquals('twig/html-extra:^3.12.0', (string) $pool->getPhpPackageDependencies()['twig/html-extra']);
    }

    public function testCanAddNpmPackageDependencies()
    {
        $pool = new Pool();

        $pool->addNpmPackageDependency(new NpmPackageDependency('tailwindcss'));

        $this->assertCount(1, $pool->getNpmPackageDependencies());
    }

    public function testCantAddSameNpmPackageDependencyTwice()
    {
        $pool = new Pool();

        $pool->addNpmPackageDependency(new NpmPackageDependency('tailwindcss'));
        $pool->addNpmPackageDependency(new NpmPackageDependency('tailwindcss'));

        $this->assertCount(1, $pool->getNpmPackageDependencies());
    }

    public function testCanAddNpmPackageDependencyWithHigherVersion()
    {
        $pool = new Pool();

        $pool->addNpmPackageDependency(new NpmPackageDependency('tailwindcss', new ConstraintVersion('^3.0.0')));

        $this->assertCount(1, $pool->getNpmPackageDependencies());
        $this->assertEquals('tailwindcss:^3.0.0', (string) $pool->getNpmPackageDependencies()['tailwindcss']);

        $pool->addNpmPackageDependency(new NpmPackageDependency('tailwindcss', new ConstraintVersion('^4.0.0')));

        $this->assertCount(1, $pool->getNpmPackageDependencies());
        $this->assertEquals('tailwindcss:^4.0.0', (string) $pool->getNpmPackageDependencies()['tailwindcss']);

        $pool->addNpmPackageDependency(new NpmPackageDependency('tailwindcss', new ConstraintVersion('^3.0.0')));

        $this->assertCount(1, $pool->getNpmPackageDependencies());
        $this->assertEquals('tailwindcss:^4.0.0', (string) $pool->getNpmPackageDependencies()['tailwindcss']);
    }

    public function testCanAddImportmapPackageDependencies()
    {
        $pool = new Pool();

        $pool->addImportmapPackageDependency(new ImportmapPackageDependency('@hotwired/stimulus'));

        $this->assertCount(1, $pool->getImportmapPackageDependencies());
    }

    public function testCantAddSameImportmapPackageDependencyTwice()
    {
        $pool = new Pool();

        $pool->addImportmapPackageDependency(new ImportmapPackageDependency('@hotwired/stimulus'));
        $pool->addImportmapPackageDependency(new ImportmapPackageDependency('@hotwired/stimulus'));

        $this->assertCount(1, $pool->getImportmapPackageDependencies());
    }
}
