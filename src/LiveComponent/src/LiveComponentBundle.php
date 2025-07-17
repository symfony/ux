<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\UX\LiveComponent\DependencyInjection\Compiler\ComponentDefaultActionPass;
use Symfony\UX\LiveComponent\DependencyInjection\Compiler\OptionalDependencyPass;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LiveComponentBundle extends Bundle
{
    public const HYDRATION_EXTENSION_TAG = 'live_component.hydration_extension';

    public function build(ContainerBuilder $container): void
    {
        // must run before Symfony\Component\Serializer\DependencyInjection\SerializerPass
        $container->addCompilerPass(new OptionalDependencyPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
        $container->addCompilerPass(new ComponentDefaultActionPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
