<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Registry;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Toolkit\Registry\GitHubRegistry;
use Symfony\UX\Toolkit\Registry\LocalRegistry;

final class RegistryFactoryTest extends KernelTestCase
{
    public static function provideRegistryNames(): array
    {
        return [
            ['shadcn', LocalRegistry::class],
            ['foo-bar', LocalRegistry::class],
            ['https://github.com/user/repo', GitHubRegistry::class],
            ['https://github.com/user/repo:1.0.0', GitHubRegistry::class],
            ['https://github.com/user/repo:2.x', GitHubRegistry::class],
            ['github.com/user/repo', GitHubRegistry::class],
            ['github.com/user/repo:1.0.0', GitHubRegistry::class],
            ['github.com/user/repo:2.x', GitHubRegistry::class],
        ];
    }

    /**
     * @dataProvider provideRegistryNames
     */
    public function testCanCreateRegistry(string $registryName, string $expectedRegistryClass): void
    {
        $registryFactory = self::getContainer()->get('ux_toolkit.registry.registry_factory');

        $registry = $registryFactory->getForKit($registryName);

        $this->assertInstanceOf($expectedRegistryClass, $registry);
    }

    public static function provideInvalidRegistryNames(): array
    {
        return [
            [''],
            ['httpppps://github.com/user/repo@kit-name:2.x'],
            ['github.com/user/repo:kit-name@1.0.0'],
            ['github.com/user/repo@2.1'],
        ];
    }

    /**
     * @dataProvider provideInvalidRegistryNames
     */
    public function testShouldFailIfRegistryIsNotFound(string $registryName): void
    {
        $registryFactory = self::getContainer()->get('ux_toolkit.registry.registry_factory');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The kit "%s" is not valid.', $registryName));

        $registryFactory->getForKit($registryName);
    }
}
