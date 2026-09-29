<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\UX\Toolkit\UXToolkitBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Symfonycasts\TailwindBundle\SymfonycastsTailwindBundle;

final class PreviewKernel extends BaseKernel
{
    use MicroKernelTrait;

    public function __construct(
        private readonly string $projectDir,
        private readonly array|bool|string $preview = true,
        private readonly bool $withTailwind = true,
    ) {
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new TwigComponentBundle();
        if ($this->withTailwind) {
            yield new SymfonycastsTailwindBundle();
        }
        yield new UXToolkitBundle();
    }

    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    public function getCacheDir(): string
    {
        return $this->projectDir.'/var/cache/'.md5(serialize([$this->preview, $this->withTailwind]));
    }

    public function getLogDir(): string
    {
        return $this->projectDir.'/var/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'S3CRET',
            'test' => true,
            'http_method_override' => false,
            'asset_mapper' => ['paths' => ['assets/']],
        ]);

        $container->extension('twig_component', [
            'anonymous_template_directory' => 'components/',
            'defaults' => [],
        ]);

        if ($this->withTailwind) {
            $container->extension('symfonycasts_tailwind', ['input_css' => ['assets/styles/app.css']]);
        }

        $container->services()
            ->alias('test.importmap.config_reader', 'asset_mapper.importmap.config_reader')->public()
            ->alias('test.asset_mapper', 'asset_mapper')->public();

        if ($this->withTailwind) {
            $container->services()->alias('test.tailwind.builder', 'tailwind.builder')->public();
        }

        if (false !== $this->preview) {
            $container->extension('ux_toolkit', ['preview' => $this->preview]);
            $container->services()->alias('test.preview.kit_registry', '.ux_toolkit.preview.kit_registry')->public();
        }
    }
}
