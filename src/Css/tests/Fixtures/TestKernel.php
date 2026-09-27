<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Css\UXCssBundle;
use Symfony\UX\DesignTokens\UXDesignTokensBundle;

final class TestKernel extends Kernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    private static ?string $temporaryRoot = null;

    /**
     * @param array<string, mixed> $uxCssConfig
     * @param array<string, mixed> $designTokens       a DTCG tree, written to a file listed in ux_design_tokens.paths
     * @param array<string, mixed> $designTokensConfig
     */
    public function __construct(
        private readonly array $uxCssConfig = [],
        string $environment = 'test',
        bool $debug = true,
        private readonly ?string $projectDir = null,
        private ?string $buildDir = null,
        private readonly array $designTokens = [],
        private readonly array $designTokensConfig = [],
        private readonly bool $designTokensBundle = true,
    ) {
        parent::__construct($environment, $debug);
    }

    public static function temporaryDirectory(): string
    {
        if (null === self::$temporaryRoot) {
            self::$temporaryRoot = sys_get_temp_dir().'/ux_css_tests/'.getmypid();
            register_shutdown_function(static fn () => new Filesystem()->remove(self::$temporaryRoot));
        }

        return self::$temporaryRoot.'/'.uniqid('', true);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        if ($this->designTokensBundle) {
            yield new UXDesignTokensBundle();
        }
        yield new UXCssBundle();
    }

    public function getProjectDir(): string
    {
        return $this->projectDir ?? $this->getBuildDir().'/project';
    }

    public function getCacheDir(): string
    {
        return $this->getBuildDir();
    }

    public function getBuildDir(): string
    {
        return $this->buildDir ??= self::temporaryDirectory();
    }

    protected function getContainerClass(): string
    {
        $configs = [$this->uxCssConfig, $this->designTokens, $this->designTokensConfig, $this->designTokensBundle];

        // a config change rebuilds the container in the same cache directory, as a YAML change does in an application
        return parent::getContainerClass().hash('xxh32', serialize($configs));
    }

    public function getLogDir(): string
    {
        return $this->getBuildDir().'/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $framework = ['secret' => 'secret', 'test' => true];
        if (null !== $this->projectDir) {
            $framework['asset_mapper'] = ['paths' => []];
        }
        $templates = null !== $this->projectDir ? $this->projectDir.'/templates' : __DIR__.'/templates';

        $container->extension('framework', $framework);
        $container->extension('twig', ['default_path' => $templates, 'strict_variables' => true]);
        $container->extension('ux_css', $this->uxCssConfig);
        $container->services()->set('logger', TestLogger::class)->public();

        $designTokens = $this->designTokensConfig;
        if ([] !== $this->designTokens) {
            $file = $this->getBuildDir().'/design/tokens.json';
            $flags = \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES;
            new Filesystem()->dumpFile($file, json_encode($this->designTokens, $flags));
            $designTokens['paths'] = [...($designTokens['paths'] ?? []), $file];
        }
        if ($this->designTokensBundle) {
            $container->extension('ux_design_tokens', $designTokens);
        }
    }

    public function process(ContainerBuilder $container): void
    {
        $ids = [
            'ux_css.class_name_generator',
            'ux_css.engine',
            'ux_css.css_generator',
            'ux_css.twig.node_visitor',
            'ux_css.stylesheet_dumper',
            'twig',
        ];

        foreach ($ids as $id) {
            $container->getDefinition($id)->setPublic(true);
        }
    }
}
