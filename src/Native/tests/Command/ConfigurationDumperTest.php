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

namespace Symfony\UX\Native\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Native\Command\ConfigurationDumper;
use Symfony\UX\Native\Configuration\Configuration;
use Symfony\UX\Native\Configuration\Rule;
use Symfony\UX\Native\ConfigurationBuilder;

final class ConfigurationDumperTest extends TestCase
{
    private string $outputDir;

    protected function setUp(): void
    {
        $this->outputDir = sys_get_temp_dir().'/ux_native_dumper_test_'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->outputDir);
    }

    public function testExecuteCallsBuildAndReturnsSuccess()
    {
        $builder = new ConfigurationBuilder($this->outputDir);
        $builder->add('/config/test.json', new Configuration(
            settings: ['debug' => true],
            rules: [new Rule(['.*'], ['context' => 'default'])],
        ));

        $command = new ConfigurationDumper($builder);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        self::assertFileExists($this->outputDir.'/config/test.json');
        self::assertStringContainsString('dumped successfully', $commandTester->getDisplay());
    }

    public function testExecuteWithNoConfigurations()
    {
        $builder = new ConfigurationBuilder($this->outputDir);

        $command = new ConfigurationDumper($builder);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        self::assertStringContainsString('dumped successfully', $commandTester->getDisplay());
    }
}
