<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\UX\Disclose\Context\ContextProviderInterface;
use Symfony\UX\Disclose\Subject\SubjectResolverInterface;

/**
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class DiscloseBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(SubjectResolverInterface::class)
            ->addTag('ux.disclose.subject_resolver');
        $container->registerForAutoconfiguration(DiscloserInterface::class)
            ->addTag('ux.disclose.discloser');
        $container->registerForAutoconfiguration(ContextProviderInterface::class)
            ->addTag('ux.disclose.context_provider');
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
