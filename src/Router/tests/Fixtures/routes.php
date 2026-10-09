<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('app_home', '/');
    $routes->add('app_blog_list', '/blog/{page}')->defaults(['page' => 1])->requirements(['page' => '\d+']);
    $routes->add('app_blog_show', '/blog/{slug}');
    $routes->add('app_about', ['en' => '/about', 'fr' => '/a-propos']);
    $routes->add('app_lang', '/{_locale}/lang')->requirements(['_locale' => 'en|fr']);
    $routes->add('app_host', '/host')->host('{subdomain}.example.com')->defaults(['subdomain' => 'www'])->requirements(['subdomain' => 'www|m']);
    $routes->add('app_secure', '/secure')->schemes(['https']);
    $routes->add('app_controller', '/controller')->controller('App\Controller\SecretController::index');
    $routes->add('app_admin_dashboard', '/admin');
    $routes->add('app_private', '/private')->options(['expose' => false]);
    $routes->add('legacy_search', '/search')->options(['expose' => true]);
    $routes->add('internal_hidden', '/hidden');
};
