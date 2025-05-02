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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Dependency\ComponentDependency;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Dependency\StimulusControllerDependency;
use Symfony\UX\Toolkit\Dependency\Version;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\KitSynchronizer;

final class KitSynchronizerTest extends KernelTestCase
{
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootKernel();
        $this->filesystem = self::getContainer()->get('filesystem');
    }

    public function testCanResolveDependencies(): void
    {
        $kitSynchronizer = new KitSynchronizer($this->filesystem);
        $kit = new Kit(Path::join(__DIR__, '../../kits/shadcn'), 'shadcn');

        $kitSynchronizer->synchronize($kit);

        $this->assertEquals([
            new PhpPackageDependency('twig/extra-bundle'),
            new PhpPackageDependency('twig/html-extra', new Version('3.12.0')),
            new PhpPackageDependency('tales-from-a-dev/twig-tailwind-extra'),
        ], $kit->getComponent('Button')->getDependencies());

        $this->assertEquals([
            new ComponentDependency('Table:Body'),
            new ComponentDependency('Table:Caption'),
            new ComponentDependency('Table:Cell'),
            new ComponentDependency('Table:Footer'),
            new ComponentDependency('Table:Head'),
            new ComponentDependency('Table:Header'),
            new ComponentDependency('Table:Row'),
            new PhpPackageDependency('tales-from-a-dev/twig-tailwind-extra'),
        ], $kit->getComponent('Table')->getDependencies());
    }

    public function testCanResolveStimulusDependencies(): void
    {
        $kitSynchronizer = new KitSynchronizer($this->filesystem);
        $kit = new Kit(Path::join(__DIR__, '../Fixtures/kits/with-stimulus-controllers'), 'kit');

        $kitSynchronizer->synchronize($kit);

        $this->assertEquals([new StimulusControllerDependency('clipboard')], $kit->getComponent('Clipboard')->getDependencies());
        $this->assertEquals([new StimulusControllerDependency('date-picker')], $kit->getComponent('DatePicker')->getDependencies());
        $this->assertEquals([new StimulusControllerDependency('local-time')], $kit->getComponent('LocalTime')->getDependencies());
        $this->assertEquals([new StimulusControllerDependency('users--list-item'), new StimulusControllerDependency('clipboard')], $kit->getComponent('UsersListItem')->getDependencies());
    }
}
