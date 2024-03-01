<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\UX\InteractiveFormValidation\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\UX\InteractiveFormValidation\Alert\Strategy as AlertStrategy;

/**
 * @author Mateusz Anders <anders_mateusz@outlook.com>
 */
final class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('interactive_form_validation');
        $treeBuilder
            ->getRootNode()
            ->children()
                ->enumNode('alert_strategy')
                    ->values(AlertStrategy::cases())
                    ->info('A strategy to use when notifying an user that a form is invalid')
                    ->defaultValue(AlertStrategy::BROWSER_NATIVE)
                ->end()
                ->booleanNode('with_alert')
                    ->info('Whether the form invalid alert should be shown to an user')
                    ->defaultTrue()
                ->end()
                ->booleanNode('enabled')
                    ->info('Whether the feature should be enabled for all forms globally')
                    ->defaultTrue()
                ->end()
            ->end()
            ;
        return $treeBuilder;
    }
}