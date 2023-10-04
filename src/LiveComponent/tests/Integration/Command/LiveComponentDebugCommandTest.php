<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Integration\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LiveComponentDebugCommandTest extends KernelTestCase
{
    public function testWithNoComponent(): void
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $display = $commandTester->getDisplay();

        $this->tableDisplayCheck($display);
    }

    public function testWithNoMatchComponent(): void
    {
        $commandTester = $this->createCommandTester();
        $result = $commandTester->execute(['name' => 'NoMatchComponent']);

        $this->assertEquals(1, $result);
        $this->assertStringContainsString('Unknown component "NoMatchComponent".', $commandTester->getDisplay());
    }

    public function testComponentWithClass(): void
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['name' => 'Component1']);

        $commandTester->assertCommandIsSuccessful();

        $display = $commandTester->getDisplay();

        $this->tableDisplayCheck($display);
        $this->assertStringContainsString('Component1', $display);
        $this->assertStringContainsString('Component\Component1', $display);
        $this->assertStringContainsString('components/Component1.html.twig', $display);
    }

    private function createCommandTester(): CommandTester
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        return new CommandTester($application->find('debug:live-component'));
    }

    private function tableDisplayCheck(string $display): void
    {
        $this->assertStringContainsString('Component', $display);
        $this->assertStringContainsString('Class', $display);
        $this->assertStringContainsString('Template', $display);
    }
}
