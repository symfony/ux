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

namespace Symfony\UX\Toolkit\Tests\Kit;

use PHPUnit\Framework\TestCase;
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
    }
}
