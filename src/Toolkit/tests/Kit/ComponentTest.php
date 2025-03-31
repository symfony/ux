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

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Component\Component;
use Symfony\UX\Toolkit\Dependency\ComponentDependency;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Dependency\Version;
use Symfony\UX\Toolkit\File\File;
use Symfony\UX\Toolkit\File\FileType;

final class ComponentTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $component = new Component('Button', [
            new File(FileType::Twig, 'templates/components/Button/Button.html.twig', 'Button.html.twig'),
        ]);

        $this->assertSame('Button', $component->name);
        $this->assertCount(1, $component->files);
        $this->assertInstanceOf(File::class, $component->files[0]);
        $this->assertNull($component->doc);
        $this->assertCount(0, $component->getDependencies());
    }

    public function testShouldFailIfComponentNameIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid component name "foobar".');

        new Component('foobar', [
            new File(FileType::Twig, 'templates/components/Button/Button.html.twig', 'Button.html.twig'),
        ]);
    }

    public function testShouldFailIfComponentHasNoFiles(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The component "Button" must have at least one file.');

        new Component('Button', []);
    }

    public function testCanAddAndGetDependencies(): void
    {
        $component = new Component('Button', [
            new File(FileType::Twig, 'templates/components/Button/Button.html.twig', 'Button.html.twig'),
        ]);

        $component->addDependency($dependency1 = new ComponentDependency('Icon'));
        $component->addDependency($dependency2 = new ComponentDependency('Label'));
        $component->addDependency($dependency3 = new PhpPackageDependency('symfony/twig-component', new Version(2, 24, 0)));

        self::assertCount(3, $component->getDependencies());
        self::assertEquals([$dependency1, $dependency2, $dependency3], $component->getDependencies());
    }

    public function testShouldNotAddDuplicateComponentDependencies(): void
    {
        $component = new Component('Button', [
            new File(FileType::Twig, 'templates/components/Button/Button.html.twig', 'Button.html.twig'),
        ]);

        $component->addDependency($dependency1 = new ComponentDependency('Icon'));
        $component->addDependency($dependency2 = new ComponentDependency('Label'));
        $component->addDependency($dependency3 = new ComponentDependency('Icon'));
        $component->addDependency($dependency4 = new PhpPackageDependency('symfony/twig-component', new Version(2, 24, 0)));

        self::assertCount(3, $component->getDependencies());
        self::assertEquals([$dependency1, $dependency2, $dependency4], $component->getDependencies());
    }

    public function testShouldReplacePhpPackageDependencyIfVersionIsHigher(): void
    {
        $component = new Component('Button', [
            new File(FileType::Twig, 'templates/components/Button/Button.html.twig', 'Button.html.twig'),
        ]);

        $component->addDependency($dependency1 = new ComponentDependency('Icon'));
        $component->addDependency($dependency2 = new ComponentDependency('Label'));
        $component->addDependency($dependency3 = new PhpPackageDependency('symfony/twig-component', new Version(2, 24, 0)));

        self::assertCount(3, $component->getDependencies());
        self::assertEquals([$dependency1, $dependency2, $dependency3], $component->getDependencies());

        $component->addDependency($dependency4 = new PhpPackageDependency('symfony/twig-component', new Version(2, 25, 0)));

        self::assertCount(3, $component->getDependencies());
        self::assertEquals([$dependency1, $dependency2, $dependency4], $component->getDependencies());
    }

    public function testShouldNotReplacePhpPackageDependencyIfVersionIsLower(): void
    {
        $component = new Component('Button', [
            new File(FileType::Twig, 'templates/components/Button/Button.html.twig', 'Button.html.twig'),
        ]);

        $component->addDependency($dependency1 = new ComponentDependency('Icon'));
        $component->addDependency($dependency2 = new ComponentDependency('Label'));
        $component->addDependency($dependency3 = new PhpPackageDependency('symfony/twig-component', new Version(2, 24, 0)));

        self::assertCount(3, $component->getDependencies());
        self::assertEquals([$dependency1, $dependency2, $dependency3], $component->getDependencies());

        $component->addDependency(new PhpPackageDependency('symfony/twig-component', new Version(2, 23, 0)));

        self::assertCount(3, $component->getDependencies());
        self::assertEquals([$dependency1, $dependency2, $dependency3], $component->getDependencies());
    }
}
