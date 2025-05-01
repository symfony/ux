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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Toolkit\Asset\StimulusController;
use Symfony\UX\Toolkit\Dependency\ComponentDependency;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Dependency\StimulusControllerDependency;
use Symfony\UX\Toolkit\File\File;
use Symfony\UX\Toolkit\File\FileType;
use Symfony\UX\Toolkit\Kit\KitFactory;

final class KitFactoryTest extends KernelTestCase
{
    public function testShouldFailIfPathIsNotAbsolute(): void
    {
        $kitFactory = $this->createKitFactory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Path "shadcn" is not absolute.');

        $kitFactory->createKitFromAbsolutePath('shadcn');
    }

    public function testShouldFailIfKitDoesNotExist(): void
    {
        $kitFactory = $this->createKitFactory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Path "%s" does not exist.', __DIR__.'/../../kits/does-not-exist'));

        $kitFactory->createKitFromAbsolutePath(__DIR__.'/../../kits/does-not-exist');
    }

    public function testCanCreateShadKit(): void
    {
        $kitFactory = $this->createKitFactory();

        $kit = $kitFactory->createKitFromAbsolutePath(__DIR__.'/../../kits/shadcn');

        $this->assertNotNull($kit);
        $this->assertNotEmpty($kit->getComponents());

        $table = $kit->getComponent('Table');

        $this->assertNotNull($table);
        $this->assertNotEmpty($table->files);
        $this->assertEquals([
            new ComponentDependency('Table:Body'),
            new ComponentDependency('Table:Caption'),
            new ComponentDependency('Table:Cell'),
            new ComponentDependency('Table:Footer'),
            new ComponentDependency('Table:Head'),
            new ComponentDependency('Table:Header'),
            new ComponentDependency('Table:Row'),
            new PhpPackageDependency('tales-from-a-dev/twig-tailwind-extra'),
        ], $table->getDependencies());
        $this->assertNotNull($table->doc);
        $this->assertStringContainsString(<<<'EOF'
# Table

A component for displaying structured data in rows and columns with support for headers, captions, and customizable styling.
EOF
            , $table->doc->markdownContent);
    }

    public function testCanHandleStimulusControllers(): void
    {
        $kitFactory = $this->createKitFactory();

        $kit = $kitFactory->createKitFromAbsolutePath(__DIR__.'/../Fixtures/kits/with-stimulus-controllers');

        $this->assertNotEmpty($kit->getComponents());

        // Assert Stimulus Controllers are registered in the Kit
        $this->assertNotEmpty($kit->getStimulusControllers());
        $this->assertEquals([
            $clipboard = new StimulusController('clipboard', [new File(FileType::StimulusController, 'assets/controllers/clipboard_controller.js', 'clipboard_controller.js')]),
            $datePicker = new StimulusController('date-picker', [new File(FileType::StimulusController, 'assets/controllers/date_picker_controller.js', 'date_picker_controller.js')]),
            $localTime = new StimulusController('local-time', [new File(FileType::StimulusController, 'assets/controllers/local-time-controller.js', 'local-time-controller.js')]),
            $usersListItem = new StimulusController('users--list-item', [new File(FileType::StimulusController, 'assets/controllers/users/list_item_controller.js', 'users/list_item_controller.js')]),
        ], $kit->getStimulusControllers());
        $this->assertEquals($clipboard, $kit->getStimulusController('clipboard'));
        $this->assertEquals($datePicker, $kit->getStimulusController('date-picker'));
        $this->assertEquals($localTime, $kit->getStimulusController('local-time'));
        $this->assertEquals($usersListItem, $kit->getStimulusController('users--list-item'));

        // Assert Stimulus Controllers are marked as Component dependencies
        $this->assertEquals([new StimulusControllerDependency('clipboard')], $kit->getComponent('Clipboard')->getDependencies());
        $this->assertEquals([new StimulusControllerDependency('date-picker')], $kit->getComponent('DatePicker')->getDependencies());
        $this->assertEquals([new StimulusControllerDependency('local-time')], $kit->getComponent('LocalTime')->getDependencies());
        $this->assertEquals([new StimulusControllerDependency('users--list-item'), new StimulusControllerDependency('clipboard')], $kit->getComponent('UsersListItem')->getDependencies());
    }

    private function createKitFactory(): KitFactory
    {
        return new KitFactory(self::getContainer()->get('filesystem'), self::getContainer()->get('ux_toolkit.kit.kit_synchronizer'));
    }
}
