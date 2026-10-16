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
    public function testDefaultsToTheAnonymousTemplateDirectory(): void
    {
        $componentDirectory = new ComponentDirectory();

        $this->assertSame('templates/components', $componentDirectory->path);
        $this->assertSame('templates/components', (string) $componentDirectory);
        $this->assertTrue($componentDirectory->isAnonymousTemplateDirectory());
    }

    public function testDefaultsToACustomAnonymousTemplateDirectory(): void
    {
        $componentDirectory = new ComponentDirectory(null, 'twig_components/');

        $this->assertSame('templates/twig_components', $componentDirectory->path);
        $this->assertSame('twig_components', $componentDirectory->anonymousTemplateDirectory);
        $this->assertTrue($componentDirectory->isAnonymousTemplateDirectory());
        $this->assertSame('', $componentDirectory->getComponentNamePrefix());
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

    #[DataProvider('provideInvalidComponentNamePrefixCases')]
    public function testShouldRejectADirectoryGivingAnInvalidComponentName(string $path): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The component directory "%s" cannot be used in a component name.', $path));

        new ComponentDirectory($path);
    }

    public static function provideInvalidComponentNamePrefixCases(): iterable
    {
        yield 'space' => ['templates/components/my ui'];
        yield 'colon' => ['templates/components/my:ui'];
        yield 'plus sign' => ['templates/components/a+b'];
    }

    public function testShouldOnlyCheckTheComponentNameBelowTheAnonymousTemplateDirectory(): void
    {
        $this->assertSame('templates/my ui', new ComponentDirectory('templates/my ui')->path);
    }

    public function testValidatePath(): void
    {
        ComponentDirectory::validatePath('templates/components/ui');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be relative to the destination directory');

        ComponentDirectory::validatePath('/templates/ui');
    }

    #[DataProvider('provideComponentNamePrefixCases')]
    public function testGetComponentNamePrefix(string $path, string $expectedPrefix): void
    {
        $this->assertSame($expectedPrefix, new ComponentDirectory($path)->getComponentNamePrefix());
    }

    public function testGetComponentNamePrefixFollowsTheAnonymousTemplateDirectory(): void
    {
        $this->assertSame('ui:', new ComponentDirectory('templates/twig_components/ui', 'twig_components')->getComponentNamePrefix());
        $this->assertSame('', new ComponentDirectory('templates/components/ui', 'twig_components')->getComponentNamePrefix());
        $this->assertSame('shadcn:ui:', new ComponentDirectory('templates/ui/shadcn/ui', 'ui')->getComponentNamePrefix());
    }

    public static function provideComponentNamePrefixCases(): iterable
    {
        yield 'default has no prefix' => ['templates/components', ''];
        yield 'nested directory prefixes the name' => ['templates/components/ui', 'ui:'];
        yield 'hyphens and dots are allowed' => ['templates/components/my-ui.v2', 'my-ui.v2:'];
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
