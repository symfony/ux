<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\DesignTokens\UXDesignTokensBundle;

/** @internal */
final class IntegrationTestKernel extends Kernel
{
    use MicroKernelTrait;

    /** @param array<string, mixed> $bundleConfig */
    public function __construct(
        private readonly array $bundleConfig,
        private readonly string $testCacheDir,
        bool $debug = false,
    ) {
        parent::__construct('test', $debug);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new UXDesignTokensBundle();
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__, 2);
    }

    public function getCacheDir(): string
    {
        return $this->testCacheDir;
    }

    public function getLogDir(): string
    {
        return $this->testCacheDir.'/log';
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                foreach ($container->getDefinitions() as $id => $definition) {
                    if (str_starts_with($id, '.ux_design_tokens.')) {
                        $definition->setPublic(true);
                    }
                }
                foreach ($container->getAliases() as $id => $alias) {
                    if (str_starts_with($id, 'Symfony\\UX\\DesignTokens\\')) {
                        $alias->setPublic(true);
                    }
                }
            }
        }, PassConfig::TYPE_BEFORE_REMOVING);
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'ux-design-tokens-test',
            'test' => true,
        ]);
        $container->extension('ux_design_tokens', $this->bundleConfig);

        $services = $container->services();
        $services->alias('test.cache_warmer', '.ux_design_tokens.cache_warmer')
            ->public()
        ;
        $services->alias('test.twig', 'twig')->public();
    }
}
