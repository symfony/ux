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
use Symfony\UX\Toolkit\Dependency\ConstraintVersion;
use Symfony\UX\Toolkit\Dependency\ImportmapPackageDependency;
use Symfony\UX\Toolkit\Dependency\NpmPackageDependency;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Dependency\RecipeDependency;
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
        $this->expectExceptionMessage('The recipe type "test" is not supported, valid types are "block", "component".');

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
        $this->expectExceptionMessage('The "dependencies" property must be an object.');

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

    public function testFromJsonWithInvalidPhpDependency()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The dependency #0 of type "composer" must be a non-empty string.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "description": "An incredible component",
                    "copy-files": {
                        "templates/": "templates/"
                    },
                    "dependencies": {
                        "composer": [""]
                    }
                }
            JSON);
    }

    public function testFromJsonWithInvalidNpmDependency()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The dependency #0 of type "npm" must be a non-empty string.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "description": "An incredible component",
                    "copy-files": {
                        "templates/": "templates/"
                    },
                    "dependencies": {
                        "composer": ["symfony/string"],
                        "npm": [""]
                    }
                }
            JSON);
    }

    public function testFromJsonWithInvalidImportmapDependency()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The dependency #0 of type "importmap" must be a non-empty string.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "description": "An incredible component",
                    "copy-files": {
                        "templates/": "templates/"
                    },
                    "dependencies": {
                        "composer": ["symfony/string"],
                        "npm": ["tailwindcss"],
                        "importmap": [""]
                    }
                }
            JSON);
    }

    public function testFromJsonWithInvalidRecipeDependency()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The dependency #0 of type "recipe" must be a non-empty string.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "description": "An incredible component",
                    "copy-files": {
                        "templates/": "templates/"
                    },
                    "dependencies": {
                        "composer": ["symfony/string"],
                        "npm": ["tailwindcss"],
                        "importmap": ["tailwindcss"],
                        "recipe": [""]
                    }
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
                    "dependencies": {
                        "composer": [
                            "tales-from-a-dev/twig-tailwind-extra:^1.0.0",
                            "symfony/ux-twig-component:^2.29"
                        ],
                        "npm": [
                            "tailwindcss@^4.0.0",
                            "@tailwindplus/elements",
                            "@tailwindplus/elements@1"
                        ],
                        "importmap": [
                            "@hotwired/stimulus"
                        ],
                        "recipe": [
                            "OtherComponent"
                        ]
                    }
                }
            JSON);

        $this->assertSame(RecipeType::Component, $manifest->type);
        $this->assertSame('MyComponent', $manifest->name);
        $this->assertSame('An incredible component', $manifest->description);
        $this->assertSame(['templates/' => 'templates/'], $manifest->copyFiles);
        $this->assertEquals([
            new RecipeDependency('OtherComponent'),
            new PhpPackageDependency('tales-from-a-dev/twig-tailwind-extra', new ConstraintVersion('^1.0.0')),
            new PhpPackageDependency('symfony/ux-twig-component', new ConstraintVersion('^2.29')),
            new NpmPackageDependency('tailwindcss', new ConstraintVersion('^4.0.0')),
            new NpmPackageDependency('@tailwindplus/elements'),
            new NpmPackageDependency('@tailwindplus/elements', new ConstraintVersion('1')),
            new ImportmapPackageDependency('@hotwired/stimulus'),
        ], $manifest->dependencies);
    }
}
