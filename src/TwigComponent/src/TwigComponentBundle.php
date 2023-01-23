<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\UX\TwigComponent\DependencyInjection\Compiler\TwigComponentPass;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TwigComponentBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new TwigComponentPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
