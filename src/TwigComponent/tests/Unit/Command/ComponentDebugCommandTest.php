<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Unit;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ComponentDebugCommandTest extends KernelTestCase
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
        $this->assertStringContainsString('Live', $display);
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
        $commandTester->execute(['name' => 'BasicComponent']);

        $commandTester->assertCommandIsSuccessful();

        $display = $commandTester->getDisplay();

        $this->tableDisplayCheck($display);
        $this->assertStringContainsString('BasicComponent', $display);
        $this->assertStringContainsString('Component\BasicComponent', $display);
        $this->assertStringContainsString('components/BasicComponent.html.twig', $display);
    }

    public function testComponentWithClassPropertiesAndCustomName(): void
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['name' => 'component_c']);

        $commandTester->assertCommandIsSuccessful();

        $display = $commandTester->getDisplay();

        $this->tableDisplayCheck($display);
        $this->assertStringContainsString('component_c', $display);
        $this->assertStringContainsString('Component\ComponentC', $display);
        $this->assertStringContainsString('components/component_c.html.twig', $display);
        $this->assertStringContainsString('$propA', $display);
        $this->assertStringContainsString('$propB', $display);
        $this->assertStringContainsString('$propC', $display);
        $this->assertStringContainsString('Mount', $display);
    }

    public function testComponentWithClassPropertiesCustomNameAndCustomTemplate(): void
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['name' => 'component_b']);

        $commandTester->assertCommandIsSuccessful();

        $display = $commandTester->getDisplay();

        $this->tableDisplayCheck($display);
        $this->assertStringContainsString('component_b', $display);
        $this->assertStringContainsString('Component\ComponentB', $display);
        $this->assertStringContainsString('components/custom1.html.twig', $display);
        $this->assertStringContainsString('string $value', $display);
        $this->assertStringContainsString('string $postValue', $display);
    }

    public function testWithAnonymousComponent(): void
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['name' => 'Button']);

        $commandTester->assertCommandIsSuccessful();

        $display = $commandTester->getDisplay();

        $this->tableDisplayCheck($display);
        $this->assertStringContainsString('Button', $display);
        $this->assertStringContainsString('Anonymous component', $display);
        $this->assertStringContainsString('components/Button.html.twig', $display);
        $this->assertStringContainsString('label', $display);
        $this->assertStringContainsString('primary = true', $display);
    }

    public function testWithDirectoryOption()
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['--dir' => 'bar']);

        $commandTester->assertCommandIsSuccessful();

        $display = $commandTester->getDisplay();

        $this->assertStringContainsString('foo:bar:baz', $display);
        $this->assertStringContainsString('OtherDirectory', $display);
        $this->assertStringContainsString('components/foo/bar/baz.html.twig', $display);
        $this->assertStringContainsString('bar/OtherDirectory.html.twig', $display);
    }

    private function createCommandTester(): CommandTester
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        return new CommandTester($application->find('debug:twig-component'));
    }

    private function tableDisplayCheck(string $display): void
    {
        $this->assertStringContainsString('Component', $display);
        $this->assertStringContainsString('Live', $display);
        $this->assertStringContainsString('Class', $display);
        $this->assertStringContainsString('Template', $display);
        $this->assertStringContainsString('Properties', $display);
    }
}
