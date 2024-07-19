<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\UX\Autocomplete\DependencyInjection\AutocompleteFormTypePass;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
final class AutocompleteBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AutocompleteFormTypePass());
    }
}
