<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\UX\CalendarLink\Ics\IcsBuilder;
use Symfony\UX\CalendarLink\Provider\GoogleCalendarLinkProvider;
use Symfony\UX\CalendarLink\Provider\IcsCalendarLinkProvider;
use Symfony\UX\CalendarLink\Provider\Office365CalendarLinkProvider;
use Symfony\UX\CalendarLink\Provider\OutlookCalendarLinkProvider;
use Symfony\UX\CalendarLink\Registry\CalendarLinkProviderRegistry;
use Symfony\UX\CalendarLink\Twig\UXCalendarLinkExtension;
use Symfony\UX\CalendarLink\Twig\UXCalendarLinkRuntime;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('ux_calendar_link.ics.builder', IcsBuilder::class);

    $services->set('ux_calendar_link.provider.google', GoogleCalendarLinkProvider::class)
        ->tag('ux_calendar_link.provider');

    $services->set('ux_calendar_link.provider.outlook', OutlookCalendarLinkProvider::class)
        ->tag('ux_calendar_link.provider');

    $services->set('ux_calendar_link.provider.office365', Office365CalendarLinkProvider::class)
        ->tag('ux_calendar_link.provider');

    $services->set('ux_calendar_link.provider.ics', IcsCalendarLinkProvider::class)
        ->args([service('ux_calendar_link.ics.builder')])
        ->tag('ux_calendar_link.provider');

    $services->set('ux_calendar_link.registry', CalendarLinkProviderRegistry::class)
        ->args([tagged_iterator('ux_calendar_link.provider')]);

    $services->alias(CalendarLinkProviderRegistry::class, 'ux_calendar_link.registry');

    $services->set('ux_calendar_link.twig.runtime', UXCalendarLinkRuntime::class)
        ->args([service('ux_calendar_link.registry')])
        ->tag('twig.runtime');

    $services->set('ux_calendar_link.twig.extension', UXCalendarLinkExtension::class)
        ->tag('twig.extension');
};
