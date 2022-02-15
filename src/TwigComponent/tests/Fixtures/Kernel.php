<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\UX\TwigComponent\Tests\Fixtures\Component\ComponentB;
use Symfony\UX\TwigComponent\TwigComponentBundle;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new TwigComponentBundle();
    }

    protected function configureContainer(ContainerConfigurator $c): void
    {
        $c->extension('framework', [
            'secret' => 'S3CRET',
            'test' => true,
            'router' => ['utf8' => true],
            'secrets' => false,
        ]);
        $c->extension('twig', [
            'default_path' => '%kernel.project_dir%/tests/Fixtures/templates',
        ]);

        $services = $c->services()
            ->defaults()
                ->autowire()
                ->autoconfigure()
            ->load(__NAMESPACE__.'\\', __DIR__)
            ->set('component_b', ComponentB::class)->autoconfigure()->autowire()
            ->set('component_d', ComponentB::class)->tag('twig.component', [
                'key' => 'component_d',
                'template' => 'components/custom2.html.twig',
            ])
        ;

        if ('missing_key' === $this->environment) {
            $services->set('missing_key', ComponentB::class)
                ->tag('twig.component')
            ;
        }
    }
}
