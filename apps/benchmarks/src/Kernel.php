<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App;

use App\Bench\IdleMachineCheck;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\Css\UXCssBundle;
use Symfony\UX\Icons\UXIconsBundle;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\Map\UXMapBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use TalesFromADev\Twig\Extra\Tailwind\Bridge\Symfony\Bundle\TalesFromADevTwigExtraTailwindBundle;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;

final class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new TwigExtraBundle();
        yield new TwigComponentBundle();
        yield new UXIconsBundle();
        if (class_exists(UXCssBundle::class)) {
            yield new UXCssBundle();
        }
        yield new TalesFromADevTwigExtraTailwindBundle();
        yield new LiveComponentBundle();
        yield new StimulusBundle();
        yield new UXMapBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        // https://symfony.com/doc/current/performance.html
        $container->parameters()->set('.container.dumper.inline_factories', true);

        $container->extension('framework', [
            'secret' => 'benchmarks',
            'enabled_locales' => ['en'],
            'csrf_protection' => false,
            'form' => ['csrf_protection' => ['enabled' => false]],
            'validation' => ['email_validation_mode' => 'html5'],
            'serializer' => ['enabled' => true],
            'property_access' => true,
        ]);

        $container->extension('twig_component', [
            'anonymous_template_directory' => 'components/',
            'defaults' => ['App\\Twig\\Components\\' => 'components/'],
        ]);

        $container->extension('ux_map', ['renderer' => 'leaflet://default']);

        $container->services()
            ->defaults()->autowire()->autoconfigure()
            ->load('App\\', __DIR__.'/')
            ->exclude([__DIR__.'/Kernel.php', __DIR__.'/Model/', __DIR__.'/Bench/{Target,LiveScenario,LiveRequest,Measurement,Round,RunInfo,Side,VendorVersions,TargetStats,PageStats,IdleMachineCheck,ComposerJsonRewriter,RoundPlan,CheckResult,Sides}.php']);

        $container->services()->set(IdleMachineCheck::class)->factory([IdleMachineCheck::class, 'withSystemProbes']);

        $container->extension('ux_icons', [
            'icon_dir' => '%kernel.project_dir%/assets/icons',
            'iconify' => ['on_demand' => false],
        ]);

        if (class_exists(UXCssBundle::class)) {
            $this->configureCss($container);
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@LiveComponentBundle/config/routes.php')->prefix('/_components');
        $routes->import(__DIR__.'/Controller/', 'attribute');
    }

    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('ux_map.renderers')->setArgument(0, [
            'default' => 'leaflet://default',
            'google' => 'google://benchmarks@default',
        ]);
    }

    private function configureCss(ContainerConfigurator $container): void
    {
        // The shadcn theme, read through the same CSS variables as the Tailwind side
        $container->extension('ux_css', [
            'preflight' => true,
            'default_tokens' => 'panda',
            'tokens' => [
                'colors' => [
                    'background' => 'var(--background)',
                    'foreground' => 'var(--foreground)',
                    'card' => ['DEFAULT' => 'var(--card)', 'foreground' => 'var(--card-foreground)'],
                    'popover' => ['DEFAULT' => 'var(--popover)', 'foreground' => 'var(--popover-foreground)'],
                    'primary' => ['DEFAULT' => 'var(--primary)', 'foreground' => 'var(--primary-foreground)'],
                    'secondary' => ['DEFAULT' => 'var(--secondary)', 'foreground' => 'var(--secondary-foreground)'],
                    'muted' => ['DEFAULT' => 'var(--muted)', 'foreground' => 'var(--muted-foreground)'],
                    'accent' => ['DEFAULT' => 'var(--accent)', 'foreground' => 'var(--accent-foreground)'],
                    'destructive' => 'var(--destructive)',
                    'border' => 'var(--border)',
                    'input' => 'var(--input)',
                    'ring' => 'var(--ring)',
                ],
                'radii' => [
                    'sm' => 'calc(var(--radius) * 0.6)',
                    'md' => 'calc(var(--radius) * 0.8)',
                    'lg' => 'var(--radius)',
                    'xl' => 'calc(var(--radius) * 1.4)',
                    '2xl' => 'calc(var(--radius) * 1.8)',
                    '3xl' => 'calc(var(--radius) * 2.2)',
                    '4xl' => 'calc(var(--radius) * 2.6)',
                ],
            ],
        ]);
    }
}
