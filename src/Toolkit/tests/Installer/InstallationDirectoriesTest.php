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
use Symfony\UX\Toolkit\Installer\InstallationDirectories;

final class InstallationDirectoriesTest extends TestCase
{
    public function testResolvesPlaceholdersToTheDefaultDirectories(): void
    {
        $directories = new InstallationDirectories();

        $this->assertSame('templates/components/Dialog.html.twig', $directories->resolve('%COMPONENTS_DIR%/Dialog.html.twig'));
        $this->assertSame('assets/controllers/dialog_controller.js', $directories->resolve('%CONTROLLERS_DIR%/dialog_controller.js'));
        $this->assertSame('assets/styles/kit.css', $directories->resolve('assets/styles/kit.css'));
        $this->assertSame('', $directories->getComponentNamePrefix());
        $this->assertSame('', $directories->getStimulusIdentifierPrefix());
    }

    public function testResolvesPlaceholdersToTheConfiguredDirectories(): void
    {
        $directories = new InstallationDirectories('templates/ui/', null, 'assets/js/controllers', null);

        $this->assertSame('templates/ui/Button.html.twig', $directories->resolve('%COMPONENTS_DIR%/Button.html.twig'));
        $this->assertSame('assets/js/controllers/dialog_controller.js', $directories->resolve('%CONTROLLERS_DIR%/dialog_controller.js'));
        $this->assertSame('', $directories->getComponentNamePrefix());
        $this->assertSame('', $directories->getStimulusIdentifierPrefix());
    }

    public function testResolvesPlaceholdersToTheConfiguredSubdirectories(): void
    {
        $directories = new InstallationDirectories('templates/components', 'ui', 'assets/js/controllers', 'ui');

        $this->assertSame('templates/components/ui/Button.html.twig', $directories->resolve('%COMPONENTS_DIR%/Button.html.twig'));
        $this->assertSame('assets/js/controllers/ui/dialog_controller.js', $directories->resolve('%CONTROLLERS_DIR%/dialog_controller.js'));
    }

    public function testRejectsAnUnknownPlaceholder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown placeholder "%TEMPLATES_DIR%" in "%TEMPLATES_DIR%/Button.html.twig"');

        new InstallationDirectories()->resolve('%TEMPLATES_DIR%/Button.html.twig');
    }

    /**
     * @return iterable<array{string, string, string}>
     */
    public static function provideSubdirectoryPrefixes(): iterable
    {
        yield 'subdirectory' => ['ui', 'ui:', 'ui--'];
        yield 'nested subdirectory' => ['ui/forms/', 'ui:forms:', 'ui--forms--'];
        yield 'subdirectory with an underscore' => ['my_ui', 'my_ui:', 'my-ui--'];
    }

    #[DataProvider('provideSubdirectoryPrefixes')]
    public function testDerivesPrefixesFromSubdirectories(string $subdirectory, string $componentPrefix, string $controllerPrefix): void
    {
        $directories = new InstallationDirectories('templates/components', $subdirectory, 'assets/controllers', $subdirectory);

        $this->assertSame($componentPrefix, $directories->getComponentNamePrefix());
        $this->assertSame($controllerPrefix, $directories->getStimulusIdentifierPrefix());
    }

    public function testRejectsAnEmptyDirectory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The directory must not be empty.');

        InstallationDirectories::normalizeDirectory('');
    }

    public function testRejectsADirectoryWithANullByte(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The directory must not contain a null byte.');

        InstallationDirectories::normalizeDirectory("templates\0");
    }

    /**
     * @return iterable<array{string}>
     */
    public static function provideAbsoluteDirectories(): iterable
    {
        yield 'absolute path' => ['/var/www/templates'];
        yield 'Windows absolute path' => ['C:\\Windows'];
        yield 'Windows drive-relative path' => ['C:assets'];
        yield 'network share' => ['\\\\server\\share'];
        yield 'stream wrapper' => ['phar://assets'];
    }

    #[DataProvider('provideAbsoluteDirectories')]
    public function testRejectsAnAbsoluteDirectory(string $directory): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The directory "%s" must be a relative path.', $directory));

        InstallationDirectories::normalizeDirectory($directory);
    }

    /**
     * @return iterable<array{string}>
     */
    public static function provideEscapingDirectories(): iterable
    {
        yield 'parent directory' => ['../../../../etc'];
        yield 'parent directory halfway' => ['templates/../../../etc'];
        yield 'parent directory with backslashes' => ['..\\..\\etc'];
        yield 'project directory in disguise' => ['assets/..'];
    }

    #[DataProvider('provideEscapingDirectories')]
    public function testRejectsADirectoryEscapingTheProject(string $directory): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The path "%s" must not escape its target directory.', $directory));

        InstallationDirectories::normalizeDirectory($directory);
    }

    public function testRejectsTheProjectDirectory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The directory "." must be inside the project.');

        InstallationDirectories::normalizeDirectory('.');
    }

    /**
     * @return iterable<array{string}>
     */
    public static function provideInvalidSubdirectories(): iterable
    {
        yield 'empty' => [''];
        yield 'current directory' => ['.'];
        yield 'parent directory' => ['../../../../etc'];
        yield 'parent directory with backslashes' => ['ui\\..\\..\\etc'];
        yield 'absolute path' => ['/ui'];
        yield 'network share' => ['\\\\server\\share'];
        yield 'stream wrapper' => ['php://filter/resource=ui'];
        yield 'space' => ['my ui'];
        yield 'colon' => ['ui:forms'];
        yield 'empty segment' => ['ui//forms'];
        yield 'null byte' => ["ui\0"];
    }

    #[DataProvider('provideInvalidSubdirectories')]
    public function testRejectsAnInvalidSubdirectory(string $subdirectory): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'The subdirectory "%s" must only contain letters, digits, "_" and "-", with "/" between directories.',
            $subdirectory,
        ));

        InstallationDirectories::normalizeSubdirectory($subdirectory);
    }

    /**
     * @return iterable<array{array{string, ?string, string, ?string}, string}>
     */
    public static function provideInvalidArguments(): iterable
    {
        yield 'components directory' => [['/components', null, 'assets', null], 'The directory "/components"'];
        yield 'components subdirectory' => [['templates', '../components', 'assets', null], 'The subdirectory "../components"'];
        yield 'controllers directory' => [['templates', null, '/controllers', null], 'The directory "/controllers"'];
        yield 'controllers subdirectory' => [['templates', null, 'assets', '../controllers'], 'The subdirectory "../controllers"'];
    }

    /**
     * @param array{string, ?string, string, ?string} $arguments
     */
    #[DataProvider('provideInvalidArguments')]
    public function testValidatesEveryArgument(array $arguments, string $expectedMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new InstallationDirectories(...$arguments);
    }
}
