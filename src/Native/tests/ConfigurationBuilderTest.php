<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\UX\Native\Configuration\Configuration;
use Symfony\UX\Native\Configuration\Rule;
use Symfony\UX\Native\ConfigurationBuilder;

final class ConfigurationBuilderTest extends TestCase
{
    private string $outputDir;

    protected function setUp(): void
    {
        $this->outputDir = sys_get_temp_dir().'/ux_native_test_'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->outputDir);
    }

    public function testAddAndHas()
    {
        $builder = new ConfigurationBuilder($this->outputDir);
        $configuration = new Configuration(settings: ['use_local_db' => true]);

        self::assertFalse($builder->has('/config/ios_v1.json'));

        $builder->add('/config/ios_v1.json', $configuration);

        self::assertTrue($builder->has('/config/ios_v1.json'));
    }

    public function testHasReturnsFalseForUnknownPath()
    {
        $builder = new ConfigurationBuilder($this->outputDir);

        self::assertFalse($builder->has('/config/unknown.json'));
    }

    public function testGet()
    {
        $builder = new ConfigurationBuilder($this->outputDir);
        $configuration = new Configuration(settings: ['use_local_db' => true]);
        $builder->add('/config/ios_v1.json', $configuration);

        $result = $builder->get('/config/ios_v1.json');

        self::assertSame($configuration, $result);
    }

    public function testGetThrowsExceptionForUnknownPath()
    {
        $builder = new ConfigurationBuilder($this->outputDir);

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('Configuration not found for path: "/config/unknown.json".');

        $builder->get('/config/unknown.json');
    }

    public function testBuild()
    {
        $builder = new ConfigurationBuilder($this->outputDir);
        $builder->add('/config/ios_v1.json', new Configuration(
            settings: ['use_local_db' => true],
            rules: [new Rule(['.*'], ['context' => 'default'])],
        ));
        $builder->add('/config/android_v1.json', new Configuration(
            settings: ['use_local_db' => false],
        ));

        $builder->build();

        self::assertFileExists($this->outputDir.'/config/ios_v1.json');
        self::assertFileExists($this->outputDir.'/config/android_v1.json');
    }

    public function testBuildEncodesJsonCorrectly()
    {
        $builder = new ConfigurationBuilder($this->outputDir);
        $builder->add('/config/ios_v1.json', new Configuration(
            settings: ['url' => 'https://example.com/path', 'label' => 'été'],
            rules: [new Rule(['.*'], ['context' => 'default'])],
        ));

        $builder->build();

        self::assertStringEqualsFile($this->outputDir.'/config/ios_v1.json', <<<JSON
            {
                "settings": {
                    "url": "https://example.com/path",
                    "label": "été"
                },
                "rules": [
                    {
                        "patterns": [
                            ".*"
                        ],
                        "properties": {
                            "context": "default"
                        }
                    }
                ]
            }
            JSON);
    }
}
