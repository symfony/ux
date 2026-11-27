<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Recipe;

use PHPUnit\Framework\Attributes\DataProvider;
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
    public function testFromJsonWithInvalidJson(): void
    {
        $this->expectException(\JsonException::class);
        $this->expectExceptionMessage('Syntax error');

        RecipeManifest::fromJson('test');
    }

    public function testFromJsonWithEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property "type" is required.');

        RecipeManifest::fromJson('{}');
    }

    public function testFromJsonWithInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The recipe type "test" is not supported, valid types are "block", "component".');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "test"
                }
            JSON);
    }

    public function testFromJsonWithMissingName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property "name" is required.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component"
                }
            JSON);
    }

    public function testFromJsonWithInvalidDependencies(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "dependencies" property must be an object.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "copy-files": {
                        "templates/": "templates/"
                    },
                    "dependencies": ["foo"]
                }
            JSON);
    }

    public function testFromJsonWithInvalidPhpDependency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The dependency #0 of type "composer" must be a non-empty string.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "copy-files": {
                        "templates/": "templates/"
                    },
                    "dependencies": {
                        "composer": [""]
                    }
                }
            JSON);
    }

    public function testFromJsonWithInvalidNpmDependency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The dependency #0 of type "npm" must be a non-empty string.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
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

    public function testFromJsonWithInvalidImportmapDependency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The dependency #0 of type "importmap" must be a non-empty string.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
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

    public function testFromJsonWithInvalidRecipeDependency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The dependency #0 of type "recipe" must be a non-empty string.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
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

    public function testFromJsonWithInvalidVersionAdded(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "version-added" property must be a non-empty string.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "version-added": "",
                    "copy-files": {
                        "templates/": "templates/"
                    }
                }
            JSON);
    }

    public function testFromJsonWithTraversalInCopyFilesSource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The path "../../../../tmp/PWNED" must not escape its target directory.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "copy-files": {
                        "../../../../tmp/PWNED": "templates/"
                    }
                }
            JSON);
    }

    public function testFromJsonWithTraversalInCopyFilesDestination(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The path "../../../../tmp/PWNED" must not escape its target directory.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "copy-files": {
                        "templates/": "../../../../tmp/PWNED"
                    }
                }
            JSON);
    }

    public function testFromJsonWithTraversalAfterAPlaceholderInCopyFilesDestination(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The path "%COMPONENTS_DIR%/../../tmp/PWNED" must not escape its target directory.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "copy-files": {
                        "templates/": "%COMPONENTS_DIR%/../../tmp/PWNED"
                    }
                }
            JSON);
    }

    public function testFromJsonWithPlaceholdersInCopyFilesDestination(): void
    {
        $manifest = RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "copy-files": {
                        "assets/controllers/": "%CONTROLLERS_DIR%/",
                        "templates/components/": "%COMPONENTS_DIR%/"
                    }
                }
            JSON);

        $this->assertSame([
            'assets/controllers/' => '%CONTROLLERS_DIR%/',
            'templates/components/' => '%COMPONENTS_DIR%/',
        ], $manifest->copyFiles);
    }

    public function testFromJsonWithUnknownPlaceholderInCopyFilesDestination(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown placeholder "%TEMPLATES_DIR%" in "%TEMPLATES_DIR%/"');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "copy-files": {
                        "templates/": "%TEMPLATES_DIR%/"
                    }
                }
            JSON);
    }

    public function testFromJsonWithBackslashTraversalInCopyFilesDestination(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The path "..\\..\\tmp\\PWNED" must not escape its target directory.');

        RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "copy-files": {
                        "templates/": "..\\\\..\\\\tmp\\\\PWNED"
                    }
                }
            JSON);
    }

    #[DataProvider('provideGlobCopyFilesSources')]
    public function testFromJsonWithGlobCharactersInCopyFilesSource(string $source): void
    {
        $json = json_encode([
            'type' => 'component',
            'name' => 'MyComponent',
            'description' => 'An incredible component',
            'copy-files' => [$source => 'templates/'],
        ], \JSON_THROW_ON_ERROR);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The path "%s" must not contain glob characters.', $source));

        RecipeManifest::fromJson($json);
    }

    public static function provideGlobCopyFilesSources(): iterable
    {
        yield 'brace expansion' => ['.{.,x}/'];
        yield 'asterisk' => ['templates/*'];
        yield 'question mark' => ['templates/?'];
        yield 'opening bracket' => ['templates/[a-z]'];
        yield 'closing bracket' => ['templates/a]'];
        yield 'opening brace' => ['templates/{a,b}'];
        yield 'closing brace' => ['templates/a}'];
    }

    #[DataProvider('provideInvalidDependencies')]
    public function testFromJsonWithInvalidDependencyVersionOrName(string $type, string $dependency): void
    {
        $json = json_encode([
            'type' => 'component',
            'name' => 'MyComponent',
            'description' => 'An incredible component',
            'copy-files' => ['templates/' => 'templates/'],
            'dependencies' => [$type => [$dependency]],
        ], \JSON_THROW_ON_ERROR);

        $this->expectException(\InvalidArgumentException::class);

        RecipeManifest::fromJson($json);
    }

    public static function provideInvalidDependencies(): iterable
    {
        yield 'composer option' => ['composer', 'a/b:^1 --no-plugins'];
        yield 'composer command' => ['composer', 'a/b:^1 --no-plugins; id'];
        yield 'composer separator' => ['composer', 'a/b:1; id'];
        yield 'composer carriage return' => ['composer', "a/b:1\r2"];
        yield 'composer markup' => ['composer', 'a/b:</><fg=black>1'];
        yield 'composer empty version' => ['composer', 'a/b:'];
        yield 'npm option' => ['npm', 'a@--ignore-scripts'];
        yield 'npm name option' => ['npm', '--ignore-scripts'];
        yield 'npm separator' => ['npm', 'a@1; id'];
        yield 'npm carriage return' => ['npm', "a@1\r2"];
        yield 'npm markup' => ['npm', 'a@</><fg=black>1'];
        yield 'importmap separator' => ['importmap', 'a; id'];
        yield 'importmap option' => ['importmap', 'a --force'];
        yield 'importmap markup' => ['importmap', '<fg=black>a</>'];
        yield 'importmap carriage return' => ['importmap', "a\r"];
    }

    #[DataProvider('provideValidVersions')]
    public function testFromJsonWithValidDependencyVersions(string $composerVersion, string $npmVersion): void
    {
        $json = json_encode([
            'type' => 'component',
            'name' => 'MyComponent',
            'description' => 'An incredible component',
            'copy-files' => ['templates/' => 'templates/'],
            'dependencies' => [
                'composer' => ['a/b:'.$composerVersion],
                'npm' => ['c@'.$npmVersion],
            ],
        ], \JSON_THROW_ON_ERROR);

        $manifest = RecipeManifest::fromJson($json);

        $this->assertEquals([
            new PhpPackageDependency('a/b', new ConstraintVersion($composerVersion)),
            new NpmPackageDependency('c', new ConstraintVersion($npmVersion)),
        ], $manifest->dependencies);
    }

    public static function provideValidVersions(): iterable
    {
        yield ['^1.0', '^5.0.0'];
        yield ['~2.3.4', '~5.0'];
        yield ['>=1.2 <2.0', '>=1.2 <2.0'];
        yield ['^1.0 || ^2.0', '^1.0 || ^2.0'];
        yield ['1.x', '1.x'];
        yield ['dev-main', 'latest'];
    }

    public function testFromJsonWithMinimumValidData(): void
    {
        $manifest = RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "copy-files": {
                        "templates/": "templates/"
                    }
                }
            JSON);

        $this->assertSame(RecipeType::Component, $manifest->type);
        $this->assertSame('MyComponent', $manifest->name);
        $this->assertSame(['templates/' => 'templates/'], $manifest->copyFiles);
        $this->assertEquals([], $manifest->dependencies);
        $this->assertNull($manifest->versionAdded);
    }

    public function testFromJsonWithValidData(): void
    {
        $manifest = RecipeManifest::fromJson(<<<JSON
                {
                    "type": "component",
                    "name": "MyComponent",
                    "version-added": "2.35",
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
        $this->assertSame('2.35', $manifest->versionAdded);
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
