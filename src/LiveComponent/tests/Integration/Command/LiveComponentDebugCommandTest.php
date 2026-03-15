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
    public function testList()
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('Name', $display);
        $this->assertStringContainsString('Class', $display);
        $this->assertStringContainsString('component1', $display);
        $this->assertStringContainsString('component2', $display);
    }

    public function testListListeningToEvent()
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['--listening' => 'the_event_name']);

        $commandTester->assertCommandIsSuccessful();

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('Name', $display);
        $this->assertStringContainsString('Class', $display);
        $this->assertStringNotContainsString('component1', $display);
        $this->assertStringContainsString('component5', $display);
    }

    public function testEmptyListListeningToEvent()
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['--listening' => 'event_not_defined']);

        $commandTester->assertCommandIsSuccessful();

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('Name', $display);
        $this->assertStringContainsString('Class', $display);
        $this->assertStringNotContainsString('component1', $display);
        $this->assertStringNotContainsString('component5', $display);
    }

    public function testWithNoMatchComponent()
    {
        $commandTester = $this->createCommandTester();
        $result = $commandTester->execute(['name' => 'NoMatchComponent']);

        $this->assertEquals(1, $result);
        $this->assertStringContainsString('Unknown component "NoMatchComponent".', $commandTester->getDisplay());
    }

    public function testNotLiveComponentsIsNotListed()
    {
        $commandTester = $this->createCommandTester();
        $result = $commandTester->execute(['name' => 'SimpleTwigComponent']);

        $this->assertEquals(1, $result);
        $this->assertStringContainsString('Unknown component "SimpleTwigComponent".', $commandTester->getDisplay());
    }

    public function testWithOnePartialMatchComponent()
    {
        $commandTester = $this->createCommandTester();
        $commandTester->setInputs([]);
        $result = $commandTester->execute(['name' => 'todo_list_']);

        $this->assertEquals(0, $result);
        // Choices
        $this->assertStringNotContainsString('] todo_list\n', $commandTester->getDisplay());
        $this->assertStringContainsString('] todo_list_with_keys', $commandTester->getDisplay());
        // Component table
        $this->assertStringContainsString('Component\\TodoListWithKeysComponent', $commandTester->getDisplay());
    }

    public function testWithMultiplePartialMatchComponent()
    {
        $commandTester = $this->createCommandTester();
        $commandTester->setInputs(['todo_list']);
        $result = $commandTester->execute(['name' => 'todo_']);

        $this->assertEquals(0, $result);
        // Choices
        $this->assertStringContainsString('Select one of the following component to display its information', $commandTester->getDisplay());
        $this->assertStringContainsString('] todo_item', $commandTester->getDisplay());
        $this->assertStringContainsString('] todo_list', $commandTester->getDisplay());
        $this->assertStringContainsString('] todo_list_with_keys', $commandTester->getDisplay());
        // Component table
        $this->assertStringNotContainsString('Component\\TodoItemComponent', $commandTester->getDisplay());
        $this->assertStringContainsString('Component\\TodoListComponent', $commandTester->getDisplay());
        $this->assertStringNotContainsString('Component\\TodoListWithKeysComponent', $commandTester->getDisplay());
    }

    public function testComponent()
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['name' => 'component1']);

        $commandTester->assertCommandIsSuccessful();

        $display = $commandTester->getDisplay();

        $this->tableDisplayCheck($display);
        $this->assertStringContainsString('component1', $display);
        $this->assertStringContainsString('Component\\Component1', $display);
    }

    public function testLivePropsWithFieldName()
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['name' => 'component3']);

        $commandTester->assertCommandIsSuccessful();

        $display = $commandTester->getDisplay();

        $this->tableDisplayCheck($display);
        $this->assertStringContainsString('component3', $display);
        $this->assertStringContainsString('LiveProps', $display);
        $this->assertStringContainsString('$prop1 (fieldName: "myProp1")', $display);
        $this->assertStringContainsString('$prop2 (fieldName: "getProp2Name()"', $display);
    }

    public function testLivePropsWithUrlMapping()
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['name' => 'component_with_url_bound_props']);

        $commandTester->assertCommandIsSuccessful();

        $display = $commandTester->getDisplay();

        $this->tableDisplayCheck($display);
        $this->assertStringContainsString('component_with_url_bound_props', $display);
        $this->assertStringContainsString('LiveProps', $display);
        $this->assertStringContainsString('$pathPropWithAlias (writable: true, url: {"as":"pathAlias","mapPath":true})', $display);
    }

    public function testWithLiveListeners()
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['name' => 'component2']);

        $commandTester->assertCommandIsSuccessful();

        $display = $commandTester->getDisplay();

        $this->tableDisplayCheck($display);
        $this->assertStringContainsString('component2', $display);
        $this->assertStringContainsString('LiveListeners', $display);
        $this->assertStringContainsString('triggerIncrease => increaseEvent1 (int $amount = 1)', $display);
        $this->assertStringContainsString('triggerIncrease => increaseEvent2 (int $amount = 1)', $display);
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
        $this->assertStringContainsString('LiveProps', $display);
        $this->assertStringContainsString('LiveListeners', $display);
    }
}
