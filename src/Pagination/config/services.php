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

use Symfony\UX\Pagination\Adapter\ArrayPaginationAdapter;
use Symfony\UX\Pagination\Cursor\CursorCodec;
use Symfony\UX\Pagination\Cursor\CursorCodecInterface;
use Symfony\UX\Pagination\PaginationInfoFormatter;
use Symfony\UX\Pagination\Paginator;
use Symfony\UX\Pagination\PaginatorInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->private()
    ;

    $services->set('ux_pagination.cursor_codec', CursorCodec::class)
        ->lazy(CursorCodecInterface::class)
    ;
    $services->alias(CursorCodecInterface::class, 'ux_pagination.cursor_codec');

    $services->set('ux_pagination.adapter.array', ArrayPaginationAdapter::class)
        ->tag('ux_pagination.adapter')
    ;

    $services->set('ux_pagination.info_formatter', PaginationInfoFormatter::class)
        ->arg('$translator', service('translator')->nullOnInvalid())
    ;

    $services->set('ux_pagination.paginator', Paginator::class)
        ->arg('$adapters', tagged_iterator('ux_pagination.adapter', defaultPriorityMethod: 'getDefaultPriority'))
        ->arg('$requestStack', service('request_stack')->nullOnInvalid())
        ->arg('$urlGenerator', service('router')->nullOnInvalid())
        ->arg('$infoFormatter', service('ux_pagination.info_formatter'))
        ->arg('$cursorCodec', service('ux_pagination.cursor_codec'))
    ;

    $services->alias(PaginatorInterface::class, 'ux_pagination.paginator');
};
