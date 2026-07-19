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
use Symfony\UX\Toolkit\Dependency\ImportmapPackageDependency;
use Symfony\UX\Toolkit\Dependency\NpmPackageDependency;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Kit\KitManifest;

final class KitManifestTest extends TestCase
{
    public function testFromJsonWithInvalidJson()
    {
        $this->expectException(\JsonException::class);
        $this->expectExceptionMessage('Syntax error');

        KitManifest::fromJson('test');
    }

    public function testFromJsonWithEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property "name" is required.');

        KitManifest::fromJson('{}');
    }

    public function testFromJsonWithMissingDescription()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property "description" is required.');

        KitManifest::fromJson(<<<JSON
                {
                    "name": "kit"
                }
            JSON);
    }

    public function testFromJsonWithMissingLicense()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property "license" is required.');

        KitManifest::fromJson(<<<JSON
                {
                    "name": "kit",
                    "description": "A kit"
                }
            JSON);
    }

    public function testFromJsonWithMissingHomepage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property "homepage" is required.');

        KitManifest::fromJson(<<<JSON
                {
                    "name": "kit",
                    "description": "A kit",
                    "license": "MIT"
                }
            JSON);
    }

    public function testFromJsonWithInvalidHomepage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid homepage URL "not-a-url".');

        KitManifest::fromJson(<<<JSON
                {
                    "name": "kit",
                    "description": "A kit",
                    "license": "MIT",
                    "homepage": "not-a-url"
                }
            JSON);
    }

    public function testFromJsonWithValidData()
    {
        $manifest = KitManifest::fromJson(<<<JSON
                {
                    "name": "kit",
                    "description": "A kit",
                    "license": "MIT",
                    "homepage": "https://example.com"
                }
            JSON);

        $this->assertSame('kit', $manifest->name);
        $this->assertSame('A kit', $manifest->description);
        $this->assertSame('MIT', $manifest->license);
        $this->assertSame('https://example.com', $manifest->homepage);
        $this->assertSame([], $manifest->dependencies);
        $this->assertNull($manifest->color);
        $this->assertNull($manifest->icon);
    }

    public function testFromJsonWithColorAndIcon()
    {
        $manifest = KitManifest::fromJson(<<<JSON
                {
                    "name": "kit",
                    "description": "A kit",
                    "license": "MIT",
                    "homepage": "https://example.com",
                    "color": "#000000",
                    "icon": "icon.svg"
                }
            JSON);

        $this->assertSame('#000000', $manifest->color);
        $this->assertSame('icon.svg', $manifest->icon);
    }

    public function testFromJsonWithDependencies()
    {
        $manifest = KitManifest::fromJson(<<<JSON
                {
                    "name": "kit",
                    "description": "A kit",
                    "license": "MIT",
                    "homepage": "https://example.com",
                    "dependencies": {
                        "composer": ["twig/extra-bundle"],
                        "npm": ["flowbite"],
                        "importmap": ["flowbite"]
                    }
                }
            JSON);

        $this->assertCount(3, $manifest->dependencies);
        $this->assertInstanceOf(PhpPackageDependency::class, $manifest->dependencies[0]);
        $this->assertInstanceOf(NpmPackageDependency::class, $manifest->dependencies[1]);
        $this->assertInstanceOf(ImportmapPackageDependency::class, $manifest->dependencies[2]);
    }

    public function testFromJsonRejectsRecipeDependency()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The dependency types "recipe" are not supported.');

        KitManifest::fromJson(<<<JSON
                {
                    "name": "kit",
                    "description": "A kit",
                    "license": "MIT",
                    "homepage": "https://example.com",
                    "dependencies": {
                        "recipe": ["Button"]
                    }
                }
            JSON);
    }
}
