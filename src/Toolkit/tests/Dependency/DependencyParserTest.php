<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Dependency;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Dependency\ConstraintVersion;
use Symfony\UX\Toolkit\Dependency\DependencyParser;
use Symfony\UX\Toolkit\Dependency\ImportmapPackageDependency;
use Symfony\UX\Toolkit\Dependency\NpmPackageDependency;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Dependency\RecipeDependency;

final class DependencyParserTest extends TestCase
{
    public function testParseNullReturnsEmptyList()
    {
        $this->assertSame([], DependencyParser::parse(null, allowRecipe: true));
        $this->assertSame([], DependencyParser::parse(null, allowRecipe: false));
    }

    public function testParseTypedDependencies()
    {
        $dependencies = DependencyParser::parse([
            'composer' => ['tales-from-a-dev/twig-tailwind-extra:^1.0.0'],
            'npm' => ['tailwindcss@^4.0.0', '@tailwindplus/elements'],
            'importmap' => ['flowbite'],
        ], allowRecipe: false);

        $this->assertEquals([
            new PhpPackageDependency('tales-from-a-dev/twig-tailwind-extra', new ConstraintVersion('^1.0.0')),
            new NpmPackageDependency('tailwindcss', new ConstraintVersion('^4.0.0')),
            new NpmPackageDependency('@tailwindplus/elements'),
            new ImportmapPackageDependency('flowbite'),
        ], $dependencies);
    }

    public function testParseRecipeWhenAllowed()
    {
        $dependencies = DependencyParser::parse(['recipe' => ['Button']], allowRecipe: true);

        $this->assertEquals([new RecipeDependency('Button')], $dependencies);
    }

    public function testParseRecipeWhenNotAllowedThrows()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The dependency types "recipe" are not supported.');

        DependencyParser::parse(['recipe' => ['Button']], allowRecipe: false);
    }

    public function testParseRejectsNonObject()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "dependencies" property must be an object.');

        DependencyParser::parse(['foo'], allowRecipe: true);
    }

    public function testParseRejectsUnsupportedType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The dependency types "unknown" are not supported.');

        DependencyParser::parse(['unknown' => ['foo']], allowRecipe: true);
    }
}
