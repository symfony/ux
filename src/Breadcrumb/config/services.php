<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\UX\Breadcrumb\BreadcrumbResolver;
use Symfony\UX\Breadcrumb\BreadcrumbTrailProvider;
use Symfony\UX\Breadcrumb\EventListener\BreadcrumbListener;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->private()
    ;

    $services->set('ux_breadcrumb.listener', BreadcrumbListener::class)
        ->arg('$rootCrumbProviders', tagged_iterator('ux_breadcrumb.root_crumb_provider'))
        ->arg('$requestAttribute', abstract_arg('request attribute name'))
        ->tag('kernel.event_subscriber')
    ;

    // Crumb expressions are static strings parsed once, and compile() is not pooled,
    // so a node-local, deploy-scoped pool beats a shared cache round-trip here.
    $services->set('.ux_breadcrumb.cache')
        ->parent('cache.system')
        ->private()
        ->tag('cache.pool')
    ;

    // Not autoconfigured on purpose: tagging every ExpressionFunctionProviderInterface
    // in the application would pull in providers written for other expression
    // languages (security, routing). Applications opt in by tagging explicitly.
    $services->set('ux_breadcrumb.expression_language', ExpressionLanguage::class)
        ->args([
            service('.ux_breadcrumb.cache'),
            tagged_iterator('ux_breadcrumb.expression_function_provider'),
        ])
    ;

    $services->set('ux_breadcrumb.resolver', BreadcrumbResolver::class)
        ->arg('$urlGenerator', service('router'))
        ->arg('$expressionLanguage', abstract_arg('expression language service'))
        ->arg('$translator', service('translator')->nullOnInvalid())
        ->arg('$defaultTranslationDomain', abstract_arg('default translation domain'))
    ;

    $services->set('ux_breadcrumb.trail_provider', BreadcrumbTrailProvider::class)
        ->arg('$requestStack', service('request_stack'))
        ->arg('$requestAttribute', abstract_arg('request attribute name'))
    ;

    $services->alias(BreadcrumbTrailProvider::class, 'ux_breadcrumb.trail_provider');
};
