<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Assert;

class AssertTest extends TestCase
{
    /**
     * @dataProvider provideValidKitNames
     */
    public function testValidKitName(string $name)
    {
        $this->expectNotToPerformAssertions();

        Assert::kitName($name);
    }

    public static function provideValidKitNames(): \Generator
    {
        yield ['my-kit'];
        yield ['my-kit-with-dashes'];
        yield ['1-my-kit'];
        yield ['my-kit-1'];
        yield ['my-kit-1-with-dashes'];
        yield ['Shadcn UI'];
        yield ['Shadcn UI-1'];
        // Single character
        yield ['a'];
        yield ['1'];
        // Maximum length (63 chars)
        yield ['a'.str_repeat('-', 61).'a'];
        // Various valid patterns
        yield ['abc123'];
        yield ['123abc'];
        yield ['a1b2c3'];
        yield ['a-b-c'];
        yield ['a1-b2-c3'];
        yield ['A1-B2-C3'];
        yield ['my_kit'];
    }

    /**
     * @dataProvider provideInvalidKitNames
     */
    public function testInvalidKitName(string $name)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Invalid kit name "%s".', $name));

        Assert::kitName($name);
    }

    public static function provideInvalidKitNames(): \Generator
    {
        yield ['my-kit-'];
        yield ['my-kit/qsd'];
        // Empty string
        yield [''];
        // Starting with hyphen
        yield ['-my-kit'];
        // Ending with hyphen
        yield ['my-kit-'];
        // Invalid characters
        yield ['my.kit'];
        yield ['my@kit'];
        // Too long (64 chars)
        yield ['a'.str_repeat('-', 62).'a'];
        // Starting with invalid character
        yield ['-abc'];
        yield ['@abc'];
        yield ['.abc'];
    }

    /**
     * @dataProvider provideValidComponentNames
     */
    public function testValidComponentName(string $name)
    {
        $this->expectNotToPerformAssertions();

        Assert::componentName($name);
    }

    public static function provideValidComponentNames(): iterable
    {
        yield ['Table'];
        yield ['TableBody'];
        yield ['Table:Body'];
        yield ['Table:Body:Header'];
        yield ['MyComponent'];
        yield ['MyComponent:SubComponent'];
        yield ['A'];
        yield ['A:B'];
        yield ['Component123'];
        yield ['Component123:Sub456'];
    }

    /**
     * @dataProvider provideInvalidComponentNames
     */
    public function testInvalidComponentName(string $name)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Invalid component name "%s".', $name));

        Assert::componentName($name);
    }

    public static function provideInvalidComponentNames(): iterable
    {
        // Empty string
        yield [''];
        // Invalid characters
        yield ['table-body'];
        yield ['table_body'];
        yield ['table.body'];
        yield ['table@body'];
        yield ['table/body'];
        // Starting with invalid characters
        yield [':Table'];
        yield ['123Table'];
        yield ['@Table'];
        // Invalid colon usage
        yield ['Table:'];
        yield ['Table::Body'];
        yield [':Table:Body'];
        // Lowercase start
        yield ['table'];
        yield ['table:Body'];
        // Numbers only
        yield ['123'];
        yield ['123:456'];
    }

    /**
     * @dataProvider provideValidPhpPackageNames
     */
    public function testValidPhpPackageName(string $name)
    {
        $this->expectNotToPerformAssertions();

        Assert::phpPackageName($name);
    }

    public static function provideValidPhpPackageNames(): iterable
    {
        yield ['twig/html-extra'];
        yield ['tales-from-a-dev/twig-tailwind-extra'];
    }

    /**
     * @dataProvider provideInvalidPhpPackageNames
     */
    public function testInvalidPhpPackageName(string $name)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Invalid PHP package name "%s".', $name));

        Assert::phpPackageName($name);
    }

    public static function provideInvalidPhpPackageNames(): iterable
    {
        yield [''];
        yield ['twig'];
        yield ['twig/html-extra/'];
        yield ['twig/html-extra/twig'];
    }

    /**
     * @dataProvider provideValidNpmPackageNames
     */
    public function testValidNpmPackageName(string $name)
    {
        $this->expectNotToPerformAssertions();

        Assert::npmPackageName($name);
    }

    public static function provideValidNpmPackageNames(): iterable
    {
        yield ['react'];
        yield ['@babel/core'];
        yield ['lodash'];
        yield ['@types/node'];
        yield ['my-package'];
        yield ['my_package'];
        yield ['my.package'];
        yield ['my-package123'];
        yield ['@scope/my-package'];
        yield ['~foo'];
    }

    /**
     * @dataProvider provideInvalidNpmPackageNames
     */
    public function testInvalidNpmPackageName(string $name)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Invalid NPM package name "%s".', $name));

        Assert::npmPackageName($name);
    }

    public static function provideInvalidNpmPackageNames(): iterable
    {
        yield [''];
        yield ['@'];
        yield ['@scope/'];
        yield ['my package'];
        yield ['my/package'];
        yield ['my@package'];
        yield ['my/package/name'];
        yield ['@scope//my-package'];
        yield [';id'];
        yield ['--save'];
        yield ['-force'];
        yield ['<fg=black>x'];
        yield ['@;id/pkg'];
        yield ["react\n"];
    }

    /**
     * @dataProvider provideNonEscapingPaths
     */
    public function testPathDoesNotEscapeDirectoryWithValidPath(string $path)
    {
        $this->expectNotToPerformAssertions();

        Assert::pathDoesNotEscapeDirectory($path);
    }

    public static function provideNonEscapingPaths(): iterable
    {
        yield ['templates/'];
        yield ['templates/components/Button.html.twig'];
        yield ['templates/components/Table/Body.html.twig'];
        // A leading ".." in a segment name is fine as long as it is not a ".." segment.
        yield ['..foo'];
        yield ['templates/..foo.twig'];
    }

    /**
     * @dataProvider provideEscapingPaths
     */
    public function testPathDoesNotEscapeDirectoryWithTraversingPath(string $path)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The path "%s" must not escape its target directory.', $path));

        Assert::pathDoesNotEscapeDirectory($path);
    }

    public static function provideEscapingPaths(): iterable
    {
        yield ['..'];
        yield ['../templates'];
        yield ['../../../../tmp/PWNED'];
        yield ['templates/../../etc/passwd'];
        // Backslash separator (Windows-style) must be rejected too.
        yield ['..\\..\\tmp\\PWNED'];
        yield ['templates\\..\\..\\etc'];
    }

    /**
     * @dataProvider provideGlobFreePaths
     */
    public function testPathHasNoGlobCharactersWithValidPath(string $path)
    {
        $this->expectNotToPerformAssertions();

        Assert::pathHasNoGlobCharacters($path);
    }

    public static function provideGlobFreePaths(): iterable
    {
        yield ['templates/'];
        yield ['templates/components/Button.html.twig'];
        yield ['templates/my-dir_1/file.name.twig'];
    }

    /**
     * @dataProvider provideGlobPaths
     */
    public function testPathHasNoGlobCharactersWithGlobPath(string $path)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The path "%s" must not contain glob characters.', $path));

        Assert::pathHasNoGlobCharacters($path);
    }

    public static function provideGlobPaths(): iterable
    {
        yield ['.{.,x}/'];
        yield ['templates/*'];
        yield ['templates/?'];
        yield ['templates/[a-z]'];
        yield ['templates/{a,b}'];
        yield ['templates/a]'];
        yield ['templates/a}'];
    }

    /**
     * @dataProvider provideValidConstraintVersions
     */
    public function testConstraintVersionWithValidVersion(string $version)
    {
        $this->expectNotToPerformAssertions();

        Assert::constraintVersion($version);
    }

    public static function provideValidConstraintVersions(): iterable
    {
        yield ['1'];
        yield ['1.2.3'];
        yield ['^1.0'];
        yield ['^1.0.0'];
        yield ['~2.3.4'];
        yield ['>=1.2 <2.0'];
        yield ['>= 1.2, < 2.0'];
        yield ['>=1.2,<2.0'];
        yield ['!=1.5'];
        yield ['^1.0 || ^2.0'];
        yield ['^1.0|^2.0'];
        yield ['1.0 - 2.0'];
        yield ['1.x'];
        yield ['1.*'];
        yield ['*'];
        yield ['v1.2.3'];
        yield ['1.0.0-beta.1+build.5'];
        yield ['1.0@dev'];
        yield ['dev-main'];
        yield ['dev-main as 1.0'];
        yield ['latest'];
        yield ['next'];
        yield ['^5.0.0'];
    }

    /**
     * @dataProvider provideInvalidConstraintVersions
     */
    public function testConstraintVersionWithInvalidVersion(string $version)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Invalid constraint version "%s".', $version));

        Assert::constraintVersion($version);
    }

    public static function provideInvalidConstraintVersions(): iterable
    {
        yield [''];
        yield [' '];
        yield ['^1 --no-plugins'];
        yield ['--no-plugins'];
        yield ['-1'];
        yield ['1; id'];
        yield ['1 && id'];
        yield ['$(id)'];
        yield ['`id`'];
        yield ['"1"'];
        yield ["'1'"];
        yield ["1\r2"];
        yield ["1\n2"];
        yield ["1\n"];
        yield ["1\t2"];
        yield ["1\0"];
        yield ['1  2'];
        yield [' 1'];
        yield ['1 '];
        yield ['<fg=black>1'];
        yield ['</><fg=black>1'];
        yield ['</>'];
        yield ['<info>1</info>'];
        yield ['>=1.2<2.0'];
        yield ['1 | | 2'];
    }

    /**
     * @dataProvider provideValidImportmapPackageNames
     */
    public function testImportmapPackageNameWithValidName(string $name)
    {
        $this->expectNotToPerformAssertions();

        Assert::importmapPackageName($name);
    }

    public static function provideValidImportmapPackageNames(): iterable
    {
        yield ['react'];
        yield ['@hotwired/stimulus'];
        yield ['bootstrap/dist/css/bootstrap.min.css'];
        yield ['@scope/package/dist/Index.js'];
    }

    /**
     * @dataProvider provideInvalidImportmapPackageNames
     */
    public function testImportmapPackageNameWithInvalidName(string $name)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Invalid importmap package name "%s".', $name));

        Assert::importmapPackageName($name);
    }

    public static function provideInvalidImportmapPackageNames(): iterable
    {
        yield [''];
        yield ['react; id'];
        yield ['react --force'];
        yield ['--force'];
        yield ['<fg=black>react</>'];
        yield ["react\r"];
        yield ["react\n"];
        yield ['react/../../etc'];
        yield ['react/.hidden'];
        yield ['react/'];
        yield ['/react'];
        yield ['React'];
    }
}
