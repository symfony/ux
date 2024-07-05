<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\UX\Translator\DependencyInjection\TranslatorCompilerPass;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @final
 *
 * @experimental
 */
class UxTranslatorBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new TranslatorCompilerPass());
    }
}
