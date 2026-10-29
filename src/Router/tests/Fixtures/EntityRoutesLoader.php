<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\Tests\Fixtures;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class EntityRoutesLoader extends Loader
{
    public function __construct(
        private EntityMetadataRegistry $registry,
    ) {
        parent::__construct();
    }

    public function load(mixed $resource, ?string $type = null): mixed
    {
        $this->registry->load();

        $routes = new RouteCollection();
        $routes->add('app_entity', new Route('/entity'));

        return $routes;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'entity' === $type;
    }
}
