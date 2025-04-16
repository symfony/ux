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
use Symfony\UX\Toolkit\Dependency\ComponentDependency;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
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

    public function testCanCreateKit(): void
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

    private function createKitFactory(): KitFactory
    {
        return new KitFactory(self::getContainer()->get('filesystem'), self::getContainer()->get('ux_toolkit.kit.dependencies_resolver'));
    }
}
