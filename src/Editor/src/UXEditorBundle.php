<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\UX\Editor\DependencyInjection\Compiler\AssertSanitizerPass;
use Symfony\UX\Editor\DependencyInjection\UXEditorExtension;

final class UXEditorBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new AssertSanitizerPass());
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return $this->extension ??= new UXEditorExtension();
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
