<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests;

use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\DataTable\DataTableFactory;
use Symfony\UX\DataTable\TypeRegistry;
use Symfony\UX\Pagination\Adapter\ArrayPaginationAdapter;
use Symfony\UX\Pagination\Adapter\DoctrineOrmAdapter;
use Symfony\UX\Pagination\Paginator;

trait DataTableTestTrait
{
    private static function createFactory(?UrlGeneratorInterface $urlGenerator = null, ?RequestStack $requestStack = null): DataTableFactory
    {
        return new DataTableFactory(
            new Paginator([new ArrayPaginationAdapter(), new DoctrineOrmAdapter()], $requestStack, $urlGenerator, defaultPerPage: 2),
            Forms::createFormFactory(),
            new TypeRegistry(),
            $urlGenerator,
        );
    }
}
