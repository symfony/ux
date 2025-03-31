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
    public function testValidKitName(string $name): void
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
        yield ['Shadcn-UI'];
        yield ['Shadcn-UI-1'];
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
    }

    /**
     * @dataProvider provideInvalidKitNames
     */
    public function testInvalidKitName(string $name): void
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
        yield ['my_kit'];
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
    public function testValidComponentName(string $name): void
    {
        Assert::componentName($name);
        $this->addToAssertionCount(1);
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
    public function testInvalidComponentName(string $name): void
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
    public function testValidPhpPackageName(string $name): void
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
    public function testInvalidPhpPackageName(string $name): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Invalid package name "%s".', $name));

        Assert::phpPackageName($name);
    }

    public static function provideInvalidPhpPackageNames(): iterable
    {
        yield [''];
        yield ['twig'];
        yield ['twig/html-extra/'];
        yield ['twig/html-extra/twig'];
    }
}
