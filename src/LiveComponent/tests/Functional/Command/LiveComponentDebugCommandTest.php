<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Functional\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LiveComponentDebugCommandTest extends KernelTestCase
{
    public function testWithNoComponent(): void
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $display = $commandTester->getDisplay();

        $this->assertStringContainsString('Component', $display);
        $this->assertStringContainsString('Class', $display);
        $this->assertStringContainsString('Template', $display);
        $this->assertStringContainsString('Type', $display);
    }

    private function createCommandTester(): CommandTester
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        return new CommandTester($application->find('debug:twig-component'));
    }
}
