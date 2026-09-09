<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Installer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Installer\ComponentDirectory;

final class ComponentDirectoryTest extends TestCase
{
    public function testDefaultsToTheKitConvention(): void
    {
        $componentDirectory = new ComponentDirectory();

        $this->assertSame('templates/components', $componentDirectory->path);
        $this->assertSame('templates/components', (string) $componentDirectory);
        $this->assertTrue($componentDirectory->isDefault());
    }

    public function testNormalizesThePath(): void
    {
        $this->assertSame('templates/ui', new ComponentDirectory('templates/ui/')->path);
        $this->assertSame('templates/components/ui', new ComponentDirectory('templates/./components/ui')->path);
    }

    public function testShouldRejectAnEmptyPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The component directory must not be empty.');

        new ComponentDirectory('   ');
    }

    public function testShouldRejectAnAbsolutePath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The component directory "/templates/ui" must be relative to the destination directory.');

        new ComponentDirectory('/templates/ui');
    }

    public function testShouldRejectAPathEscapingTheDestination(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The path "../../../tmp/PWNED" must not escape its target directory.');

        new ComponentDirectory('../../../tmp/PWNED');
    }

    #[DataProvider('provideComponentNamePrefixCases')]
    public function testGetComponentNamePrefix(string $path, string $expectedPrefix): void
    {
        $this->assertSame($expectedPrefix, new ComponentDirectory($path)->getComponentNamePrefix());
    }

    public static function provideComponentNamePrefixCases(): iterable
    {
        yield 'default has no prefix' => ['templates/components', ''];
        yield 'nested directory prefixes the name' => ['templates/components/ui', 'ui:'];
        yield 'deeply nested directory' => ['templates/components/acme/ui', 'acme:ui:'];
        yield 'sibling directory has no computable prefix' => ['templates/ui', ''];
        yield 'directory outside templates has no computable prefix' => ['assets/components', ''];
    }

    #[DataProvider('provideResolveDestinationCases')]
    public function testResolveDestination(string $path, string $destination, string $expected): void
    {
        $this->assertSame($expected, new ComponentDirectory($path)->resolveDestination($destination));
    }

    public static function provideResolveDestinationCases(): iterable
    {
        yield 'default leaves the path untouched' => [
            'templates/components',
            'templates/components/Button.html.twig',
            'templates/components/Button.html.twig',
        ];

        yield 'components are re-rooted' => [
            'templates/ui',
            'templates/components/Button.html.twig',
            'templates/ui/Button.html.twig',
        ];

        yield 'nested components keep their sub-path' => [
            'templates/components/ui',
            'templates/components/Dialog/Content.html.twig',
            'templates/components/ui/Dialog/Content.html.twig',
        ];

        yield 'stimulus controllers are left alone' => [
            'templates/ui',
            'assets/controllers/dialog_controller.js',
            'assets/controllers/dialog_controller.js',
        ];

        yield 'unrelated template paths are left alone' => [
            'templates/ui',
            'templates/emails/welcome.html.twig',
            'templates/emails/welcome.html.twig',
        ];
    }

    #[DataProvider('provideComponentNameCases')]
    public function testComponentName(string $relativePathName, ?string $expected): void
    {
        $this->assertSame($expected, ComponentDirectory::componentName($relativePathName));
    }

    public static function provideComponentNameCases(): iterable
    {
        yield ['templates/components/Button.html.twig', 'Button'];
        yield ['templates/components/Dialog/Content.html.twig', 'Dialog:Content'];
        yield ['assets/controllers/dialog_controller.js', null];
        yield ['templates/emails/welcome.html.twig', null];
        yield ['templates/components/README.md', null];
        yield ['templates/components/.html.twig', null];
    }
}
