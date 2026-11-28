<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests\Fixtures\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;

#[Breadcrumb(label: 'product.index.breadcrumb')]
final class ProductIndexController
{
    public function __invoke(): Response
    {
        return new Response('index');
    }
}
