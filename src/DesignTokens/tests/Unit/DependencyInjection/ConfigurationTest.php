<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\UX\DesignTokens\UXDesignTokensBundle;

#[CoversClass(UXDesignTokensBundle::class)]
final class ConfigurationTest extends TestCase
{
    private function getExtension(): ExtensionInterface
    {
        $bundle = new UXDesignTokensBundle();
        $extension = $bundle->getContainerExtension();
        self::assertNotNull($extension);

        return $extension;
    }

    private function processConfig(array $config = []): array
    {
        $extension = $this->getExtension();
        $configuration = $extension->getConfiguration([], new ContainerBuilder());
        self::assertNotNull($configuration);

        return new Processor()->processConfiguration($configuration, [$config]);
    }

    public function testDefaultConfigurationHasExpectedKeys(): void
    {
        $config = $this->processConfig();

        self::assertSame([], $config['paths']);
        self::assertSame(['path' => null, 'inputs' => []], $config['resolver']);
        self::assertSame(['modifier' => 'scheme', 'light' => 'light', 'dark' => 'dark'], $config['color_scheme']);
        self::assertSame('dt', $config['css_prefix']);
    }

    public function testConfigurationAcceptsCustomValues(): void
    {
        $config = $this->processConfig([
            'paths' => ['/tokens/base.json'],
            'resolver' => ['path' => '/theme.resolver.json', 'inputs' => ['theme' => 'dark']],
            'color_scheme' => ['modifier' => 'mode', 'light' => 'day', 'dark' => 'night'],
        ]);

        self::assertSame(['/tokens/base.json'], $config['paths']);
        self::assertSame(['path' => '/theme.resolver.json', 'inputs' => ['theme' => 'dark']], $config['resolver']);
        self::assertSame(['modifier' => 'mode', 'light' => 'day', 'dark' => 'night'], $config['color_scheme']);
    }

    public function testResolverInputNamesKeepTheirHyphens(): void
    {
        $config = $this->processConfig(['resolver' => ['path' => '/theme.resolver.json', 'inputs' => ['color-scheme' => 'dark']]]);

        self::assertSame(['color-scheme' => 'dark'], $config['resolver']['inputs']);
    }

    /** @param array<string, mixed> $config */
    #[DataProvider('invalidConfigurations')]
    public function testConfigurationRejectsAnInvalidValue(array $config, ?string $message): void
    {
        $this->expectException(InvalidConfigurationException::class);
        if (null !== $message) {
            $this->expectExceptionMessage($message);
        }

        $this->processConfig($config);
    }

    /** @return iterable<string, array{array<string, mixed>, ?string}> */
    public static function invalidConfigurations(): iterable
    {
        yield 'nested resolver input value' => [['resolver' => ['path' => '/theme.resolver.json', 'inputs' => ['theme' => ['dark']]]], 'Resolver input values must be strings or numbers.'];
        yield 'null resolver input value' => [['resolver' => ['path' => '/theme.resolver.json', 'inputs' => ['theme' => null]]], 'Resolver input values must be strings or numbers.'];
        yield 'resolver inputs without a resolver document' => [['resolver' => ['inputs' => ['theme' => 'dark']]], '"resolver.inputs" requires "resolver.path"'];
        yield 'empty path' => [['paths' => ['']], null];
    }
}
