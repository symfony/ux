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
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\UX\TwigComponent\Tests\Fixtures\Component\ComponentA;
use Symfony\UX\TwigComponent\Tests\Fixtures\Component\ComponentB;
use Symfony\UX\TwigComponent\Tests\Fixtures\Component\ComponentC;
use Symfony\UX\TwigComponent\Tests\Fixtures\Component\WithAttributes;
use Symfony\UX\TwigComponent\Tests\Fixtures\Service\ServiceA;
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

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->loadFromExtension('framework', [
            'secret' => 'S3CRET',
            'test' => true,
            'router' => ['utf8' => true],
            'secrets' => false,
        ]);
        $c->loadFromExtension('twig', [
            'default_path' => '%kernel.project_dir%/tests/Fixtures/templates',
        ]);

        $c->register(ServiceA::class)->setAutoconfigured(true)->setAutowired(true);
        $c->register(ComponentA::class)->setAutoconfigured(true)->setAutowired(true);
        $c->register('component_b', ComponentB::class)->setAutoconfigured(true)->setAutowired(true);
        $c->register(ComponentC::class)->setAutoconfigured(true)->setAutowired(true);
        $c->register(WithAttributes::class)->setAutoconfigured(true)->setAutowired(true);
        $c->register('component_d', ComponentB::class)->addTag('twig.component', [
            'key' => 'component_d',
            'template' => 'components/custom2.html.twig',
        ]);

        if ('missing_key' === $this->environment) {
            $c->register('missing_key', ComponentB::class)->setAutowired(true)->addTag('twig.component');
        }
    }
}
