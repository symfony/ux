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
use Symfony\UX\Toolkit\Dependency\ImportmapPackageDependency;
use Symfony\UX\Toolkit\Dependency\NpmPackageDependency;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Dependency\RecipeDependency;
use Symfony\UX\Toolkit\Dependency\Version;
use Symfony\UX\Toolkit\Recipe\RecipeManifest;
use Symfony\UX\Toolkit\Recipe\RecipeType;

final class RecipeManifestTest extends TestCase
{
    public function testFromJsonWithInvalidJson()
    {
        $this->expectException(\JsonException::class);
        $this->expectExceptionMessage('Syntax error');

        RecipeManifest::fromJson('test');
    }

    public function testFromJsonWithEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property "type" is required.');

        RecipeManifest::fromJson('{}');
    }

    public function testFromJsonWithInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The recipe type "test" is not supported.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "test"
                }
            JSON);
    }

    public function testFromJsonWithMissingName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property "name" is required.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component"
                }
            JSON);
    }

    public function testFromJsonWithMissingDescription()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property "description" is required.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent"
                }
            JSON);
    }

    public function testFromJsonWithInvalidDependencies()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Each dependency must be an associative array.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "description": "An incredible component",
                    "copy-files": {
                        "templates/": "templates/"
                    },
                    "dependencies": ["foo"]
                }
            JSON);
    }

    public function testFromJsonWithInvalidDependenciesType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The dependency #0 is missing "type" field.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "description": "An incredible component",
                    "copy-files": {
                        "templates/": "templates/"
                    },
                    "dependencies": [
                        {"key": "value"}
                    ]
                }
            JSON);
    }

    public function testFromJsonWithInvalidPhpDependency()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The dependency #0 of type "php" is missing "name" field.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "description": "An incredible component",
                    "copy-files": {
                        "templates/": "templates/"
                    },
                    "dependencies": [
                        {"type": "php"}
                    ]
                }
            JSON);
    }

    public function testFromJsonWithInvalidNpmDependency()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The dependency #1 of type "npm" is missing "name" field.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "description": "An incredible component",
                    "copy-files": {
                        "templates/": "templates/"
                    },
                    "dependencies": [
                        {"type": "php", "name": "symfony/string"},
                        {"type": "npm"}
                    ]
                }
            JSON);
    }

    public function testFromJsonWithInvalidImportmapDependency()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The dependency #2 of type "importmap" is missing "package" field.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "description": "An incredible component",
                    "copy-files": {
                        "templates/": "templates/"
                    },
                    "dependencies": [
                        {"type": "php", "name": "symfony/string"},
                        {"type": "npm", "name": "bar"},
                        {"type": "importmap"}
                    ]
                }
            JSON);
    }

    public function testFromJsonWithInvalidRecipeDependency()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The dependency #3 of type "recipe" is missing "name" field.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "description": "An incredible component",
                    "copy-files": {
                        "templates/": "templates/"
                    },
                    "dependencies": [
                        {"type": "php", "name": "symfony/string"},
                        {"type": "npm", "name": "bar"},
                        {"type": "importmap", "package": "baz"},
                        {"type": "recipe"}
                    ]
                }
            JSON);
    }

    public function testFromJsonWithMinimumValidData()
    {
        $manifest = RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "description": "An incredible component",
                    "copy-files": {
                        "templates/": "templates/"
                    }
                }
            JSON);

        $this->assertSame(RecipeType::Component, $manifest->type);
        $this->assertSame('MyComponent', $manifest->name);
        $this->assertSame('An incredible component', $manifest->description);
        $this->assertSame(['templates/' => 'templates/'], $manifest->copyFiles);
        $this->assertEquals([], $manifest->dependencies);
    }

    public function testFromJsonWithValidData()
    {
        $manifest = RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "description": "An incredible component",
                    "copy-files": {
                        "templates/": "templates/"
                    },
                    "dependencies": [
                        {
                            "type": "php",
                            "name": "tales-from-a-dev/twig-tailwind-extra"
                        },
                        {
                            "type": "php",
                            "name": "symfony/ux-twig-component",
                            "version": "^2.29"
                        },
                        {
                            "type": "npm",
                            "name": "tailwindcss",
                            "version": "^4.0.0"
                        },
                        {
                            "type": "importmap",
                            "package": "@hotwired/stimulus"
                        },
                        {
                            "type": "recipe",
                            "name": "OtherComponent"
                        }
                    ]
                }
            JSON);

        $this->assertSame(RecipeType::Component, $manifest->type);
        $this->assertSame('MyComponent', $manifest->name);
        $this->assertSame('An incredible component', $manifest->description);
        $this->assertSame(['templates/' => 'templates/'], $manifest->copyFiles);
        $this->assertEquals([
            new PhpPackageDependency('tales-from-a-dev/twig-tailwind-extra', null),
            new PhpPackageDependency('symfony/ux-twig-component', new Version('^2.29')),
            new NpmPackageDependency('tailwindcss', new Version('^4.0.0')),
            new ImportmapPackageDependency('@hotwired/stimulus'),
            new RecipeDependency('OtherComponent'),
        ], $manifest->dependencies);
    }
}
