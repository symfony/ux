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
use Symfony\UX\Toolkit\Component\Component;
use Symfony\UX\Toolkit\Dependency\ComponentDependency;
use Symfony\UX\Toolkit\Dependency\DependenciesResolver;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Dependency\Version;
use Symfony\UX\Toolkit\File\File;
use Symfony\UX\Toolkit\File\FileType;
use Symfony\UX\Toolkit\Kit\Kit;

final class DependenciesResolverTest extends KernelTestCase
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
        $dependenciesResolver = new DependenciesResolver($this->filesystem);

        $kit = new Kit(Path::join(__DIR__, '../../kits/shadcn'), 'shadcn', 'https://shadcn.com', ['Shadcn'], 'MIT');
        $kit->addComponent($button = new Component('Button', [new File(FileType::Twig, 'templates/components/Button.html.twig', 'Button.html.twig')]));
        $kit->addComponent($table = new Component('Table', [new File(FileType::Twig, 'templates/components/Table.html.twig', 'Table.html.twig')]));
        $kit->addComponent(new Component('Table:Row', [new File(FileType::Twig, 'templates/components/Table/Row.html.twig', 'Table/Row.html.twig')]));
        $kit->addComponent(new Component('Table:Cell', [new File(FileType::Twig, 'templates/components/Table/Cell.html.twig', 'Table/Cell.html.twig')]));

        $this->assertCount(0, $button->getDependencies());
        $this->assertCount(0, $table->getDependencies());

        $dependenciesResolver->resolveDependencies($kit);

        $this->assertEquals([
            new PhpPackageDependency('twig/extra-bundle'),
            new PhpPackageDependency('twig/html-extra', new Version(3, 12, 0)),
            new PhpPackageDependency('tales-from-a-dev/twig-tailwind-extra'),
        ], $button->getDependencies());

        $this->assertEquals([
            new ComponentDependency('Table:Row'),
            new ComponentDependency('Table:Cell'),
            new PhpPackageDependency('tales-from-a-dev/twig-tailwind-extra'),
        ], $table->getDependencies());
    }
}
