<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Twig;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Twig\DesignTokenExtension;
use Symfony\UX\DesignTokens\Twig\DesignTokenRuntime;
use Twig\Node\Node;
use Twig\TwigFunction;

#[CoversClass(DesignTokenExtension::class)]
final class DesignTokenExtensionTest extends TestCase
{
    public function testDeclaresExactlyTheThreeFunctions(): void
    {
        $names = array_map(static fn (TwigFunction $function): string => $function->getName(), new DesignTokenExtension()->getFunctions());

        self::assertSame(['ux_token', 'ux_token_css', 'ux_token_stylesheet'], $names);
    }

    /** @param list<string> $safe */
    #[DataProvider('functions')]
    public function testEachFunctionPointsAtTheRuntimeWithItsEscaping(string $name, string $method, array $safe): void
    {
        $functions = array_values(array_filter(new DesignTokenExtension()->getFunctions(), static fn (TwigFunction $function): bool => $name === $function->getName()));

        self::assertCount(1, $functions);
        self::assertSame([DesignTokenRuntime::class, $method], $functions[0]->getCallable());
        self::assertSame($safe, $functions[0]->getSafe(new Node()));
    }

    /** @return iterable<string, array{string, string, list<string>}> */
    public static function functions(): iterable
    {
        yield 'ux_token' => ['ux_token', 'getToken', []];
        yield 'ux_token_css' => ['ux_token_css', 'renderCss', ['html']];
        yield 'ux_token_stylesheet' => ['ux_token_stylesheet', 'renderStylesheet', ['html']];
    }
}
