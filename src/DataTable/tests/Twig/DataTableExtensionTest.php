<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\UX\DataTable\Exception\LogicException;
use Symfony\UX\DataTable\Twig\DataTableExtension;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\TwigFunction;

final class DataTableExtensionTest extends TestCase
{
    public function testRendersIconsWithUxIcons(): void
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([IconRuntime::class => static fn () => new IconRuntime()]));
        $twig->addFunction(new TwigFunction('ux_icon', [IconRuntime::class, 'renderIcon']));

        self::assertSame('<svg data-icon="tabler:eye" class="icon"></svg>', new DataTableExtension()->renderIcon($twig, 'tabler:eye', ['class' => 'icon']));
        self::assertSame('', new DataTableExtension()->renderIcon($twig, null));
    }

    public function testRendersIconsWithAClosure(): void
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addFunction(new TwigFunction('ux_icon', static fn (string $icon, array $attributes): string => '<svg>'.$icon.'</svg>'));

        self::assertSame('<svg>tabler:eye</svg>', new DataTableExtension()->renderIcon($twig, 'tabler:eye'));
    }

    public function testRendersNoIconWithoutUxIcons(): void
    {
        self::assertSame('', new DataTableExtension()->renderIcon(new Environment(new ArrayLoader()), 'tabler:eye'));
    }

    public function testGeneratesCsrfTokens(): void
    {
        $manager = $this->createMock(CsrfTokenManagerInterface::class);
        $manager->method('getToken')->with('delete-1')->willReturn(new CsrfToken('delete-1', 'token'));

        self::assertSame('token', new DataTableExtension($manager)->getCsrfToken('delete-1'));
    }

    public function testRequiresTheCsrfTokenManager(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot generate the CSRF token "delete-1" of an action: install symfony/security-csrf and enable "framework.csrf_protection".');

        new DataTableExtension()->getCsrfToken('delete-1');
    }
}

final class IconRuntime
{
    /**
     * @param array<string, string> $attributes
     */
    public function renderIcon(string $icon, array $attributes = []): string
    {
        return \sprintf('<svg data-icon="%s" class="%s"></svg>', $icon, $attributes['class'] ?? '');
    }
}
