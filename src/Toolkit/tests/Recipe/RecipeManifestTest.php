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

    public function testFromJsonWithMissingLicense()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property "copy-files" is required.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "description": "An incredible component"
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
        $this->expectExceptionMessage('The dependency type is missing for dependency #0, add "type" key.');

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
        $this->expectExceptionMessage('The package name is missing for dependency #0, add "package" key.');

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

    public function testFromJsonWithInvalidRecipeDependency()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The recipe name is missing for dependency #0, add "name" key.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "description": "An incredible component",
                    "copy-files": {
                        "templates/": "templates/"
                    },
                    "dependencies": [
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
                            "package": "symfony/ux-twig-component:^2.29"
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
            new PhpPackageDependency('symfony/ux-twig-component', new Version('^2.29')),
            new RecipeDependency('OtherComponent'),
        ], $manifest->dependencies);
    }
}
